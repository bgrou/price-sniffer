<?php
namespace App\Jobs;

use AllowDynamicProperties;
use App\Imports\ChunkedSheetImport;
use App\Repositories\SheetRepository;
use App\Services\SheetImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;



#[AllowDynamicProperties]
class ProcessSheetImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $filePath;

    private array $currencyPatterns = [
        'USD' => '$#', 'EUR' => '€#', 'GBP' => '£#', 'RUB' => '₽#',
        'AUD' => 'A$#', 'CAD' => 'C$#', 'INR' => '₹#', 'CNY' => '¥#',
        'BRL' => 'R$#', 'HKD' => 'HK$#', 'SGD' => 'S$#', 'TRY' => '₺#'
    ];

    /**
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @throws Exception
     * @throws ConnectionException
     */
    public function handle(ChunkedSheetImport $import, SheetRepository $sheetRepository): void
    {
        // Get the full path of the stored file
        $fullPath = storage_path("app/public/" . $this->filePath);
        $currencyKey = $this->hasCurrencyFormatInSheet($fullPath);
        $sheetName = basename($fullPath);
        $sheet = $sheetRepository->firstOrCreate(
            ['name' => $sheetName],
            ['currency_key' => $currencyKey]
        );
        $import->setFullPath($fullPath);
        $import->setSheet($sheet);
        $import->setCurrencyKey($currencyKey);
        Excel::import($import, $fullPath);
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
}
