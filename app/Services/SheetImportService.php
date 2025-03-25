<?php

namespace App\Services;
use App\Imports\ChunkedSheetImport;
use App\Models\Product;
use App\Models\ProductEntry;
use App\Models\Sheet;
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

    /**
     * @throws ConnectionException
     */

    public function truncate() {
        ProductEntry::truncate();
        Product::truncate();
        Sheet::truncate();
    }
}
