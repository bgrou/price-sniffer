<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExchangeRates extends Model
{
    protected $table = 'exchange_rates';
    protected $primaryKey = 'symbol';
    protected $fillable = ['symbol', 'rate', 'status'];
    public $timestamps = false;
    public $incrementing = false;

    public function sheet(): HasMany
    {
        return $this->hasMany(Sheet::class, 'currency_key', 'symbol');
    }
}
