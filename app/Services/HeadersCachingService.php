<?php

namespace App\Services;
use App\Repositories\HeadersCachingRepository;
use Illuminate\Support\Facades\Log;

class HeadersCachingService
{
    public function __construct(protected HeadersCachingRepository $repository){}

    public function firstOrCreate(array $headers, array $values)
    {
        Log::info("FoC: " . print_r($headers, true));
        $headers = base64_encode(json_encode($headers));
        $values = base64_encode(json_encode($values));
        $this->repository->firstOrCreate(['key' => $headers], ['value' => $values]);
    }

    public function get(array $headers)
    {
        Log::info("get: " . print_r($headers, true));
        $headers = base64_encode(json_encode($headers));
        $output = $this->repository->find($headers);
        return json_decode(base64_decode($output), true);
    }
}
