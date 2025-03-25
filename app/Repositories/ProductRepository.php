<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductRepository
{
    public function __construct(protected Product $model){}
    public function firstOrCreate(array $find, array $arr): ?Product
    {
        try {
            return $this->model->updateOrCreate($find, $arr);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return null;
        }
    }

    public function getAllBestPrice(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::with('highestPrice.sheet')->get();
    }
}
