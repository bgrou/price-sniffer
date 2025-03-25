<?php

namespace App\Imports;

use AllowDynamicProperties;
use App\Models\Sheet;
use App\Repositories\ProductEntryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SheetRepository;
use App\Services\AIService;
use App\Services\HeadersCachingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\BeforeImport;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Barryvdh\Debugbar\Facade as Debugbar;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;


#[AllowDynamicProperties]
class ChunkedSheetImport implements ToCollection, WithChunkReading
{
    private Sheet $sheet;
    private bool $headersFound = false;
    private array $headersPos = [];
    private $progressCallback = null; // Callback function for progress tracking
    private int $totalRows = 0;
    private int $processedRows = 0;

    public function __construct(
        private readonly AIService              $ai,
        private readonly ProductEntryRepository $productEntryRepository,
        private readonly ProductRepository      $productRepository,
        private readonly HeadersCachingService $headersCachingService
    ) {}

    public function setFullPath(string $fullPath): self
    {
        $this->fullPath = $fullPath;
        return $this;
    }

    public function setCurrencyKey(string $currencyKey): string
    {
        $this->currencyKey = $currencyKey;
        return $this->currencyKey;
    }

    public function setSheet(Sheet $sheet): Sheet
    {
        $this->sheet = $sheet;
        return $this->sheet;
    }
    
    /**
     * Set a callback function to track progress
     * 
     * @param callable $callback Function that accepts a percentage (0-100)
     * @return self
     */
    public function setProgressCallback(callable $callback): self
    {
        $this->progressCallback = $callback;
        return $this;
    }


    /**
     * @throws Exception
     */
    public function collection(Collection $rows)
    {
        try {
            Log::channel('dbg')->info("Importing chunked sheet...");
            // Convert the Collection into an array
            $dataArray = $rows->toArray();
            
            // Update total rows count for progress tracking
            if ($this->totalRows === 0) {
                // Try to get total row count from the file if not already set
                if (isset($this->fullPath) && file_exists($this->fullPath)) {
                    try {
                        $spreadsheet = IOFactory::load($this->fullPath);
                        $worksheet = $spreadsheet->getActiveSheet();
                        $this->totalRows = $worksheet->getHighestDataRow();
                    } catch (\Exception $e) {
                        Log::warning('Could not determine total rows: ' . $e->getMessage());
                        // Fallback to an estimate
                        $this->totalRows = count($dataArray) * 2;
                    }
                } else {
                    // Fallback to an estimate
                    $this->totalRows = count($dataArray) * 2;
                }
            }
            
            // Parse the data using the array form
            $parsedRows = $this->parseData($dataArray);
            
            // Store the parsed rows in batches
            $this->store($parsedRows);
            
            // Update processed rows count
            $this->processedRows += count($dataArray);
            
            // Report progress
            $this->reportProgress();
            
            // Force garbage collection after processing
            gc_collect_cycles();
        } catch (\Exception $e) {
            Log::error('Error processing chunk: ' . $e->getMessage());
            // Continue with next chunk instead of failing the entire import
        }
    }

    public function chunkSize(): int
    {
        return 500; // Reduced chunk size for better memory management
    }

    /**
     * @throws Exception
     */

    private function parseData(array $data): array
    {
        Log::channel('dbg')->info("Processing chunk with " . count($data) . " rows");
        gc_collect_cycles(); // Force garbage collection before processing chunk
        $result = [];
        foreach ($data as $row) {
            Log::channel('dbg')->info(print_r($row,true));
            if ($this->isRowEmpty($row)) {
            Log::channel('dbg')->info("Row empty");
                continue;
            }

            if (!$this->headersFound && $this->containsHeader($row)) {
                Log::channel('dbg')->info("Header Found");

                $this->headersFound = true;
                $cache = $this->headersCachingService->get($row);

                if (!$cache) {
                    Log::channel('dbg')->info("Ai used for headers");
                    $this->headersPos = $this->ai->request($row)->original;
                    $this->headersCachingService->firstOrCreate($row, $this->headersPos);
                } else {
                    Log::channel('dbg')->info("Cache used for headers");
                    $this->headersPos = $cache;
                }
                Log::channel('dbg')->info("Header Positions: " . print_r($this->headersPos, true));
                continue;
            }
            Log::channel('dbg')->info("Row processing:");
            if ($this->headersFound) {
                $extractedFields = [];
                foreach (["EAN", "Description", "Stock", "Price"] as $field) {

                    $position = $this->headersPos[$field] ?? null;

                    Log::channel('dbg')->info("Position: " . $position);
                    $extractedFields[$field] = ($position !== null && isset($row[$position])) ? $row[$position] : null;
                    Log::channel('dbg')->info("Extracted Field: " . $extractedFields[$field]);
                    Log::channel('dbg')->info("Extracted Fields: " . print_r($extractedFields, true));
                }
                if (!empty($extractedFields['EAN']) && !empty($extractedFields['Description']) && !empty($extractedFields['Price'])) {
                    $result[] = $extractedFields;
                }
            }
            Log::channel('dbg')->info(print_r($result,true));
        }
        return $result;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (!is_null($cell) && trim((string)$cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function containsHeader(array $row): bool
    {
        $headerKeywords = ['EAN', 'UPC', 'Code'];
        foreach ($headerKeywords as $keyword) {
            foreach ($row as $cell) {
                if (stripos($cell, $keyword) !== false) {
                    return true;
                }
            }
        }
        return false;
    }



    private function store($rows): void
    {
        if (empty($rows)) {
            Log::info('No valid rows to store');
            return;
        }

        $batchSize = 50; // Smaller batch size for better performance
        $batches = array_chunk($rows, $batchSize);
        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            // Report progress for each batch
            if ($totalBatches > 1) {
                $batchProgress = ($batchIndex / $totalBatches) * 100;
                $this->reportProgress($batchProgress);
            }
            try {
                // Use database transaction for each batch
                DB::beginTransaction();
                
                foreach ($batch as $row) {
                    if (empty($row['EAN']) || strlen($row['EAN']) < 5) {
                        continue; // Skip invalid EAN
                    }
                    
                    try {
                        // Create product if it doesn't exist
                        $this->productRepository->firstOrCreate(
                            ['ean' => $row['EAN']], 
                            ['description' => $row['Description'] ?? 'No description']
                        );
                        
                        // Clean and normalize price value
                        $cleanPrice = preg_replace('/[^0-9,.]/', '', $row['Price'] ?? '0');
                        $cleanPrice = str_replace(',', '.', $cleanPrice);
                        if (!is_numeric($cleanPrice)) {
                            $cleanPrice = 0;
                        }
                        
                        // Create product entry
                        $this->productEntryRepository->firstOrCreate(
                            ['product_ean' => $row['EAN'], 'sheet_id' => $this->sheet->id],
                            ['quantity' => $row['Stock'] ?? 0, 'price' => $cleanPrice]
                        );
                    } catch (QueryException $e) {
                        // Log database errors but continue processing
                        Log::error('Database error processing row with EAN ' . ($row['EAN'] ?? 'unknown') . ': ' . $e->getMessage());
                        continue;
                    } catch (\Exception $e) {
                        Log::error('Error processing row with EAN ' . ($row['EAN'] ?? 'unknown') . ': ' . $e->getMessage());
                        continue;
                    }
                }
                
                DB::commit();
                Log::info('Batch ' . $batchIndex . ' committed successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error processing batch ' . $batchIndex . ': ' . $e->getMessage());
            }
            
            // Force garbage collection after each batch
            gc_collect_cycles();
        }
    }
    
    /**
     * Report progress to the callback function if set
     * 
     * @param float|null $overrideProgress Optional override percentage (0-100)
     * @return void
     */
    private function reportProgress(?float $overrideProgress = null): void
    {
        if (!$this->progressCallback) {
            return;
        }
        
        $progress = $overrideProgress;
        
        if ($progress === null && $this->totalRows > 0) {
            $progress = min(($this->processedRows / $this->totalRows) * 100, 99);
        }
        
        if ($progress !== null) {
            call_user_func($this->progressCallback, $progress);
        }
    }
}
