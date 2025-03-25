<?php

namespace App\Services;
use App\Repositories\HeadersCachingRepository;
use Illuminate\Support\Facades\Log;

class HeadersCachingService
{
    public function __construct(protected HeadersCachingRepository $repository){}

    public function firstOrCreate(array $headers, array $values)
    {
        $headers = base64_encode(json_encode($headers));
        $values = base64_encode(json_encode($values));
        $this->repository->firstOrCreate(['key' => $headers], ['value' => $values]);
    }

    public function get(array $headers)
    {
        Log::channel('dbg')->info($headers);
        $headers = base64_encode(json_encode($headers));
        $output = $this->repository->find($headers);
        Log::channel('dbg')->info($output);
        return json_decode(base64_decode($output), true);
    }
}
