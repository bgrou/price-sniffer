<?php

namespace App\Services;

use App\Models\ExchangeRates;
use Illuminate\Support\Facades\Log;

class ExchangeRatesService
{
    public function getAll()
    {
        return ExchangeRates::select('symbol', 'rate', 'status')
            ->get()
            ->all();
    }



    public function update($rates): void
    {
        foreach ($rates as $symbol => $newRate) {
            if (empty($symbol)) {
                Log::error('Symbol cannot be empty for rate update.');
                continue;
            }

            $currentRate = ExchangeRates::where('symbol', $symbol)->first();
            if ($currentRate != null) {
                if ($newRate > $currentRate->rate) {
                    $status = 'up';
                } elseif ($newRate < $currentRate->rate) {
                    $status = 'down';
                } else {
                    $status = 'equal';
                }
                $currentRate->rate = $newRate;
                $currentRate->status = $status;
                $currentRate->save();
            } else {
                ExchangeRates::create([
                    'symbol' => $symbol,
                    'rate' => $newRate,
                    'status' => 'equal'
                ]);
            }
        }
    }
}
