<?php

namespace App\Console\Commands;

use App\Services\ExchangeRatesService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRatesApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:exchange-rates-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Exchange Rates.';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRatesService $exchangeRatesService): ?array
    {
        $accessKey = '452198bcf15af9d028077d1bd78f4a3b';
        $base = 'EUR';
        $symbols = 'EUR,GBP,AUD,BRL,CAD,CNY,HKD,SGD,TRY';

        try {
            $response = Http::get('https://api.exchangeratesapi.io/v1/latest', [
                'access_key' => $accessKey,
                'base' => $base,
                'symbols' => $symbols,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $gbpRates = $this->convertEURBaseToGBP($data['rates']);
                $exchangeRatesService->update($gbpRates);
                return $gbpRates;
            } else {
                Log::error('Erro na requisição da API: ' . $response->status() . ', Body - ' . $response->body());
                return null;
            }
        } catch (Exception $e) {
            Log::error('Erro ao fazer a requisição da API: ' . $e->getMessage());
            return null;
        }
    }

    public function convertEURBaseToGBP($data): array
    {
        $eurRates = $data;
        $gbpRate = $eurRates['GBP'];

        $gbpRates = [];
        foreach ($eurRates as $currency => $rate) {
            if ($currency == 'EUR') {
                $gbpRates[$currency] = 1 / $gbpRate;
            } else {
                $gbpRates[$currency] = $rate / $gbpRate;
            }
        }

        $gbpRates['GBP'] = 1;
        return $gbpRates;
    }
}
