<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeadersCaching extends Model
{
    protected $table = 'headers_caching';
    protected $primaryKey = 'key';
    protected $fillable = ['key', 'value'];
    public $timestamps = false;
    public $incrementing = false;
}
