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


#[AllowDynamicProperties]
class ChunkedSheetImport implements ToCollection, WithChunkReading
{
    private Sheet $sheet;



    private bool $headersFound = false;
    private array $headersPos = [];

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
     * @throws Exception
     */
    public function collection(Collection $rows)
    {
        Debugbar::info("Importing chunked sheet...");
        // Convert the Collection into an array
        $dataArray = $rows->toArray();
        Debugbar::info($dataArray);

        // Parse the data using the array form
        $rows = [];
        $rows1 = $this->parseData($dataArray);
        // Store the parsed rows
        $this->store($rows1);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * @throws Exception
     */

    private function parseData(array $data): array
    {
        $result = [];
        foreach ($data as $row) {
            Debugbar::info(print_r($row,true));
            if ($this->isRowEmpty($row)) {
            Debugbar::info("Row empty");
                continue;
            }

            if (!$this->headersFound && $this->containsHeader($row)) {
                Debugbar::info("Header Found");

                $this->headersFound = true;
                $cache = $this->headersCachingService->get($row);

                if (!$cache) {
                    Debugbar::info("Ai used for headers");
                    $this->headersPos = $this->ai->request($row)->original;
                    $this->headersCachingService->firstOrCreate($row, $this->headersPos);
                } else {
                    Debugbar::info("Cache used for headers");
                    $this->headersPos = $cache;
                }
                Debugbar::info("Header Positions: " . print_r($this->headersPos, true));
                continue;
            }
            Debugbar::info("Row processing:");
            if ($this->headersFound) {
                $extractedFields = [];
                foreach (["EAN", "Description", "Stock", "Price"] as $field) {

                    $position = $this->headersPos[$field] ?? null;

                    Debugbar::info("Position: " . $position);
                    $extractedFields[$field] = ($position !== null && isset($row[$position])) ? $row[$position] : null;
                    Debugbar::info("Extracted Field: " . $extractedFields[$field]);
                    Debugbar::info("Extracted Fields: " . print_r($extractedFields, true));
                }
                if (!empty($extractedFields['EAN']) && !empty($extractedFields['Description']) && !empty($extractedFields['Price'])) {
                    $result[] = $extractedFields;
                }
            }
            Debugbar::info(print_r($result,true));
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
        foreach ($rows as $row) {
            Log::info(print_r($row, true));
            if (strlen($row['EAN']) >= 5) {
                $this->productRepository->firstOrCreate(['ean' => $row['EAN']], ['description' => $row['Description']]);
                $cleanPrice = preg_replace('/[^0-9,.]/', '', $row['Price']);
                $this->productEntryRepository->firstOrCreate(
                    ['product_ean' => $row['EAN'], 'sheet_id' => $this->sheet->id],
                    ['quantity' => $row['Stock'], 'price' => $cleanPrice]
                );
            }
        }
    }
}
