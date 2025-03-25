<?php
namespace App\Jobs;

use AllowDynamicProperties;
use App\Imports\ChunkedSheetImport;
use App\Models\FileUpload;
use App\Repositories\SheetRepository;
use App\Services\SheetImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;



#[AllowDynamicProperties]
class ProcessSheetImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Allow 3 retries
    public $timeout = 600; // 10 minutes timeout
    public $maxExceptions = 3; // Maximum number of exceptions allowed

    private string $filePath;
    private ?int $fileUploadId;

    private array $currencyPatterns = [
        'USD' => '$#', 'EUR' => '€#', 'GBP' => '£#', 'RUB' => '₽#',
        'AUD' => 'A$#', 'CAD' => 'C$#', 'INR' => '₹#', 'CNY' => '¥#',
        'BRL' => 'R$#', 'HKD' => 'HK$#', 'SGD' => 'S$#', 'TRY' => '₺#'
    ];

    /**
     * @param string $filePath
     * @param int|null $fileUploadId
     */
    public function __construct(string $filePath, ?int $fileUploadId = null)
    {
        $this->filePath = $filePath;
        $this->fileUploadId = $fileUploadId;
    }

    /**
     * @throws Exception
     * @throws ConnectionException
     */
    public function handle(ChunkedSheetImport $import, SheetRepository $sheetRepository): void
    {
        // Update file status to processing if we have a file upload ID
        $this->updateFileStatus('processing', 10);
        
        try {
            // Get the full path of the stored file
            $fullPath = storage_path("app/public/" . $this->filePath);
            
            // Check if file exists
            if (!file_exists($fullPath)) {
                Log::error("File not found: {$fullPath}");
                $this->updateFileStatus('error', 0, 'File not found: ' . $this->filePath);
                return;
            }
            
            Log::info("Processing file: {$this->filePath}");
            $this->updateFileStatus('processing', 20);
            
            // Detect currency format
            $currencyKey = $this->hasCurrencyFormatInSheet($fullPath);
            $sheetName = basename($fullPath);
            $this->updateFileStatus('processing', 30);
            
            // Create or retrieve sheet record
            $sheet = $sheetRepository->firstOrCreate(
                ['name' => $sheetName],
                ['currency_key' => $currencyKey]
            );
            
            Log::info("Sheet record created for: {$sheetName}");
            $this->updateFileStatus('processing', 50);
            
            // Configure import
            $import->setFullPath($fullPath);
            $import->setSheet($sheet);
            $import->setCurrencyKey($currencyKey);
            $import->setProgressCallback(function($progress) {
                // Map progress from 0-100 to 50-95 range for our UI
                $scaledProgress = 50 + ($progress * 0.45);
                $this->updateFileStatus('processing', (int)$scaledProgress);
            });
            
            // Start import process with memory optimization
            Excel::import($import, $fullPath);
            
            Log::info("Import completed successfully for: {$sheetName}");
            $this->updateFileStatus('completed', 100);
        } catch (\Exception $e) {
            Log::error('Sheet import failed for file ' . $this->filePath . ': ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            // Update file status to error
            $this->updateFileStatus('error', 0, $e->getMessage());
            
            if ($this->attempts() >= $this->tries) {
                Log::error('Maximum retry attempts reached for ' . $this->filePath);
                throw $e; // Re-throw if we've exhausted retries
            }
            
            // Exponential backoff for retries
            $delay = pow(2, $this->attempts()) * 15;
            Log::info("Releasing job back to queue with {$delay} second delay");
            $this->release($delay); // Release back to queue with exponential backoff
        }
    }

    private function hasCurrencyFormatInSheet($filePath): ?string
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $style = $sheet->getStyle($cell->getCoordinate());
                $formatCode = $style->getNumberFormat()->getFormatCode();

                if ($formatCode !== "General" && $formatCode !== "0") {
                    if ($currency = $this->isCurrencyFormat($formatCode)) {
                        return $currency;
                    }
                }

                $cellValue = $cell->getValue();
                if ($currency = $this->detectCurrencyInCellValue($cellValue)) {
                    return $currency;
                }
            }
        }
        return null;
    }

    private function detectCurrencyInCellValue($cellValue): ?string
    {
        $cellValue = strtolower(trim($cellValue));
        foreach ($this->currencyPatterns as $currency => $pattern) {
            if (str_contains($cellValue, str_replace('#', '', $pattern)) || str_contains($cellValue, strtolower($currency))) {
                return $currency;
            }
        }
        return null;
    }

    private function isCurrencyFormat($formatCode): ?string
    {
        $formatCode = str_replace(['"', '\\'], '', $formatCode);

        foreach ($this->currencyPatterns as $currency => $pattern) {
            $symbol = str_replace('#', '', $pattern);
            if (str_contains($formatCode, $symbol) || str_contains($formatCode, $currency)) {
                return $currency;
            }
        }
        return null;
    }
    
    /**
     * Update the file upload status in the database
     *
     * @param string $status
     * @param int $progress
     * @param string|null $errorMessage
     * @return void
     */
    private function updateFileStatus(string $status, int $progress, ?string $errorMessage = null): void
    {
        if (!$this->fileUploadId) {
            return;
        }
        
        try {
            $fileUpload = FileUpload::find($this->fileUploadId);
            if ($fileUpload) {
                $fileUpload->update([
                    'status' => $status,
                    'progress' => $progress,
                    'error_message' => $errorMessage,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update file status: ' . $e->getMessage());
        }
    }
}
