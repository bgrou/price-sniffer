<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function export()
    {
        return Excel::download(new ProductsExport, 'products.xlsx');
    }
}
