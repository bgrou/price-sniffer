<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'ean';
    protected $fillable = ['ean', 'description'];
    public $timestamps = false;
    public $incrementing = false;
    public function product_entries(): HasMany
    {
        return $this->hasMany(ProductEntry::class, 'product_ean', 'ean');
    }
}
