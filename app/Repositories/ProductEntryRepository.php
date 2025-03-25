<?php
namespace App\Repositories;

use App\Models\ProductEntry;

class ProductEntryRepository
{
    public function __construct(protected ProductEntry $productEntry){}

    public function firstOrCreate($find, $input): object
    {
        return $this->productEntry->firstOrCreate($find, $input);
    }
}
