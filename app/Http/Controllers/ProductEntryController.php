<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductEntryResource;
use App\Models\ExchangeRates;
use App\Models\ProductEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductEntryController extends Controller
{
    public function index()
    {
        $exchangeRates = ExchangeRates::pluck('rate', 'symbol')->toArray();
        $products = ProductEntry::with(['product', 'sheet'])->get();

        $data = $products->map(function ($product) use ($exchangeRates) {
            $exchangeRate = $exchangeRates[$product->sheet->currency_key] ?? 1;
            $priceInTargetCurrency = $product->price / $exchangeRate;

            return [
                "ean" => $product->product_ean,
                "description" => $product->product->description ?? 'x',
                "stock" => $product->quantity,
                "price" => $priceInTargetCurrency,
                "sheet" => $product->sheet->name,
            ];
        })->toArray();

        return response()->json($data);
    }
}
