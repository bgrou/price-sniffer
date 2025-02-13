<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public function __construct(protected Product $model){}
    public function firstOrCreate(array $find, array $arr): Product
    {
        return $this->model->firstOrCreate($find, $arr);
    }
}
