<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sheet extends Model
{
    protected $table = 'sheets';
    protected $fillable = ['name', 'currency_key'];

    public function product_entries(): HasMany
    {
        return $this->hasMany(ProductEntry::class, 'sheet_id');
    }

    public function exchange_rate(): HasOne
    {
        return $this->hasOne(ExchangeRates::class, 'symbol');
    }
}
