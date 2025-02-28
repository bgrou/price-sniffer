<?php

namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService
{
    public function __construct(private readonly ProductRepository $repository){}

    public function getAllBestPrice(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getAllBestPrice();
    }
}
