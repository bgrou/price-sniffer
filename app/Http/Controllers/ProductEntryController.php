<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductEntryResource;
use App\Models\ProductEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductEntryController extends Controller
{
    public function index()
    {
        $products = ProductEntry::with(['product', 'sheet'])->get();
        $data = $products->map(function ($product) {
            return [
                "ean" => $product->product_ean,
                "description" => $product->product->description ?? '',
                "stock" => $product->quantity,
                "price" => $product->price,
                "sheet" => $product->sheet->name,
            ];
        })->toArray();
        Log::info(print_r($data, true));
        return response()->json($data);
    }
}
