<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'ean';
    protected $fillable = ['ean', 'description'];
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = [
        'ean' => 'string',
    ];

    public function product_entries(): HasMany
    {
        return $this->hasMany(ProductEntry::class, 'product_ean', 'ean');
    }

    public function lowestPrice(): HasOne
    {
        return $this->hasOne(ProductEntry::class, 'product_ean', 'ean')
            ->orderBy('price', 'asc'); // Order by price ascending to get the lowest price
    }
}
