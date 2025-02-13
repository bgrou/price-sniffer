<?php

namespace App\Services;
use App\Repositories\ProductEntryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SheetRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;

class SheetImportService
{
    public function __construct(protected AIService $ai,
                                protected SheetRepository $sheetRepository,
                                protected ProductEntryRepository $productEntryRepository,
                                protected ProductRepository $productRepository,
                                protected HeadersCachingService $headersCachingService) {}
    private function isCurrencyFormat($formatCode): ?string
    {
        // Common patterns for currency formats
        $currencyPatterns = [
            'USD' => '$#',
            'EUR' => '€#',
            'GBP' => '£#',
            'RUB' => '₽#',
            'AUD' => 'A$#',
            'CAD' => 'C$#',
            'INR' => '₹#',
            'CNY' => '¥#',
            'BRL' => 'R$#',
            'HKD' => 'HK$#',
            'SGD' => 'S$#',
            'TRY' => '₺#',
        ];
        $formatCode = str_replace('"', '', $formatCode); // Removes double quotes
        $formatCode = str_replace('\\\\', '', $formatCode);
        foreach ($currencyPatterns as $currency => $pattern) {
            // Convert fnmatch-like pattern to regex
            $regexPattern = '/' . preg_quote($pattern, '/') . '/';
            if (preg_match($regexPattern, $formatCode)) {
                return $currency; // Return detected currency
            }
        }
        return null; // Return null if no match is found
    }

    /**
     * @throws Exception
     */
    public function hasCurrencyFormatInSheet($filePath): ?string
    {
        // Load the Excel file
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Iterate through all rows and cells
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                // Get the cell style
                $style = $sheet->getStyle($cell->getCoordinate());
                $formatCode = $style->getNumberFormat()->getFormatCode();
                if ($formatCode !== "General" && $formatCode !== "0") {
                    // Check if the format is a currency format
                    if ($currency = $this->isCurrencyFormat($formatCode)) {
                        return $currency; // Currency format found
                    }
                }
            }
        }

        return null; // No currency format found
    }

    /**
     * @throws ConnectionException
     */
    public function xlsxToObj(string $xlsxFilePath): array
    {
        $data = Excel::toArray((object)[], $xlsxFilePath);
        return $this->parseData($data);
    }

    /**
     * @throws ConnectionException
     */
    private function parseData(array $data): array
    {
        $result = [];
        $headerFound = false;
        $headersPos = null;

        foreach ($data as $sheet)
        {
            foreach ($sheet as $row)
            {
                if ($this->isRowEmpty($row))
                {
                    continue;
                }

                // Identify headers and map their positions using AI
                if (!$headerFound && $this->containsHeader($row)) {
                    $headerFound = true;

                    $cache = $this->headersCachingService->get($row);
                    Log::info("Cache: ". print_r($cache, true));
                    if(!$cache) {
                        Log::info("AI USED");
                        $headersPos = $this->ai->request($row)->original;
                        $this->headersCachingService->firstOrCreate($row, $headersPos);
                    } else {
                        Log::info("Cache USED");
                        $headersPos = $cache;
                    }
                    //implement caching for this
                    continue;
                }

                // Process rows after headers are identified
                if ($headerFound) {
                    // Extract fields based on headersPos
                    $extractedFields = [];
                    foreach (["EAN", "Description", "Stock", "Price"] as $field) {
                        $position = $headersPos[$field] ?? null;
                        $extractedFields[$field] = ($position !== null && isset($row[$position])) ? $row[$position] : null;
                    }

                    // Append the extracted fields as an associative array to the result
                    if (!empty($extractedFields['EAN']) &&
                        !empty($extractedFields['Description']) &&
                        !empty($extractedFields['Price'])) {
                        $result[] = $extractedFields;
                    }
                }
            }
        }
        return $result;
    }

    private function isRowEmpty(array $row): bool
    {
        // Check if all elements in the row are empty (null, '', or whitespace)
        foreach ($row as $cell) {
            if (!is_null($cell) && trim((string)$cell) !== '') {
                return false; // Row is not empty
            }
        }
        return true; // Row is empty
    }

    private function containsHeader(array $row): bool
    {
        $headerKeywords = ['EAN', 'UPC', 'Code', 'Description', 'Price'];

        foreach ($headerKeywords as $keyword) {
            foreach ($row as $cell) {
                if (stripos($cell, $keyword) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    public function store ($sheetName, $rows, $currency): void {
        $sheet = $this->sheetRepository->firstOrCreate(['name' => $sheetName], ['currency_key' => $currency]);
        foreach ($rows as $row) {
            if(strlen($row['EAN']) >= 5) {
                $this->productRepository->firstOrCreate(['ean' => $row['EAN']], ['description' => $row['Description']]);

                $this->productEntryRepository->firstOrCreate(['product_ean' => $row['EAN'], 'sheet_id' => $sheet->id],
                    ['quantity' => $row['Stock'], 'price' => $row['Price']]);
            }
        }
    }
}
