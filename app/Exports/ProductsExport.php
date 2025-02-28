<?php

namespace App\Exports;

use App\Models\ExchangeRates;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithEvents, ShouldAutoSize, WithColumnFormatting
{
    /**
     * Retrieve all products with the best price and its sheet.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection(): \Illuminate\Support\Collection
    {
        // Preload exchange rates into a key-value array (symbol => rate)
        $exchangeRates = ExchangeRates::pluck('rate', 'symbol')->toArray();

        // Fetch products with their lowest price and related sheet
        return Product::with(['lowestPrice.sheet'])->orderBy('description')->get()->map(function ($product) use ($exchangeRates) {
            $lowestPrice = $product->lowestPrice;
            $sheet = $lowestPrice ? $lowestPrice->sheet : null;

            if ($lowestPrice && $sheet) {
                // Get the currency of the sheet
                $currency = $sheet->currency_key;

                // Get the exchange rate for the currency (default to 1 if not found)
                $exchangeRate = $exchangeRates[$currency] ?? 1;

                // Adjust the price using the exchange rate
                $convertedPrice = round($lowestPrice->price / $exchangeRate, 2);

                return [
                    'ean' => $product->ean, // Preserve leading zeros for Excel
                    'description' => $product->description ?? 'N/A',
                    'sheet_name' => $sheet->name,
                    'stock' => $lowestPrice->quantity,
                    'best_price' => $convertedPrice,
                ];
            }

            // Handle cases where the lowest price or sheet is missing
            return [
                'ean' => $product->ean,
                'description' => $product->description ?? 'N/A',
                'sheet_name' => null,
                'stock' => null,
                'best_price' => null,
            ];
        });
    }

    /**
     * Map each product to a row in the export.
     *
     * @param array $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row['ean'],              // EAN with leading zeros preserved
            $row['description'],      // Description
            $row['sheet_name'],       // Sheet Name
            $row['stock'],            // Stock quantity
            $row['best_price'],       // Best Price after applying exchange rate
        ];
    }

    /**
     * Define the column headings for the export.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'EAN',
            'Description',
            'Sheet Name',
            'Stock',
            'Best Price(£)',
        ];
    }

    /**
     * Register events to style and format the sheet.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRange = 'A1:E1';

                // Apply auto filter to the header row (Row 1).
                $sheet->setAutoFilter($headerRange);

                // Style the header row (A1:E1)
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FFFFDE21', // Yellow header color
                        ],
                    ],
                ]);

                // Explicitly set EAN column as text to avoid scientific notation
                foreach ($sheet->getRowIterator(2) as $row) { // Start from row 2 (data rows)
                    $cell = "A" . $row->getRowIndex(); // Column A (EAN)
                    $value = $sheet->getCell($cell)->getValue();
                    if ($value) {
                        // Set cell value explicitly as text
                        $sheet->setCellValueExplicit($cell, (string)$value, DataType::TYPE_STRING);
                    }
                }

                // Get the last row number.
                $highestRow = $sheet->getHighestRow();

                // Define red fill style for missing sheet or price.
                $redFill = [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FFFF7F7F',  // Bright red color for missing data.
                        ],
                    ],
                ];

                // Loop through each data row (starting from row 2 since row 1 is header).
                for ($row = 2; $row <= $highestRow; ++$row) {
                    // Column C holds the sheet name, Column E holds the best price.
                    $sheetName = $sheet->getCell("C{$row}")->getValue();
                    $bestPrice = $sheet->getCell("E{$row}")->getValue();

                    // If either is empty, apply the red fill to the entire row.
                    if (empty($sheetName) || empty($bestPrice)) {
                        $rowRange = "A{$row}:E{$row}";
                        $sheet->getStyle($rowRange)->applyFromArray($redFill);
                    }
                }
            },
        ];
    }

    /**
     * Define column formats for specific columns.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'A' => '@', // Column A (EAN) formatted as text to preserve leading zeros
        ];
    }
}
