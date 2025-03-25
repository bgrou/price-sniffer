<?php

namespace App\Http\Controllers;

use App\Services\ExchangeRatesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ExchangeRatesController extends Controller
{
    public function __construct(private readonly ExchangeRatesService $service) {}

    public function index(): \Inertia\Response
    {
        $rates = $this->service->getAll();
        return Inertia::render('CurrencyRates', [
            'rates' => $rates
        ]);
    }
}
