<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Thiagoprz\CompositeKey\HasCompositeKey;

class ProductEntry extends Model
{
    use HasCompositeKey;

    protected $table = 'product_entries';
    protected $primaryKey = ['product_ean', 'sheet_id'];
    protected $fillable = ['product_ean', 'sheet_id', 'quantity', 'price'];
    public $timestamps = false;
    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'product_ean' => 'string',
    ];


    public function sheet(): belongsTo {
        return $this->belongsTo(Sheet::class, 'sheet_id');
    }

    public function product(): belongsTo {
        return $this->belongsTo(Product::class, 'product_ean', 'ean');
    }
}
