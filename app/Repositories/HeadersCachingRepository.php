<?php
namespace App\Repositories;

use App\Models\HeadersCaching;
use App\Models\ProductEntry;
use Illuminate\Support\Facades\Log;

class HeadersCachingRepository
{
    public function __construct(protected HeadersCaching $headersCaching){}

    public function firstOrCreate($find, $input): object
    {
        Log::info($find);
        Log::info($input);
        return $this->headersCaching->firstOrCreate($find, $input);
    }

    public function find($key) {
        Log::info("Key: " . $key);
        $output = HeadersCaching::
            where('key', $key)
            ->first()
            ->value ?? null;

        Log::info("Output: " . $output);
        return $output;
    }
}
