<?php
namespace App\Repositories;

use App\Models\ProductEntry;
use App\Models\Sheet;

class SheetRepository
{
    public function __construct(
        protected Sheet $sheet
    ){}

    public function firstOrCreate($find, $input): object
    {
        return $this->sheet->firstOrCreate($find, $input);
    }
}
