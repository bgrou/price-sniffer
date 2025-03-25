<?php

use App\Http\Controllers\ExchangeRatesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductEntryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SheetImportController;
use App\Services\ExchangeRatesService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('UploadSheets');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/upload-sheet', [SheetImportController::class, 'import'])->name('upload-sheet');
    Route::get('/upload-status', [SheetImportController::class, 'status'])->name('upload-status');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/product-entries', [ProductEntryController::class, 'index']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/best-prices-sheet', [ProductController::class, 'export'])->name('best_prices_sheet');
});

Route::get('/best_price_finder', function () {
    return Inertia::render('BestPriceFinder');
})->middleware(['auth', 'verified'])->name('best_price_finder');

Route::get('/upload_sheets', function () {
    return Inertia::render('UploadSheets');
})->middleware(['auth', 'verified'])->name('upload_sheets');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('/delete-sheets', [SheetImportController::class, 'truncate'])->name('delete_sheets');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/currency_rates', [ExchangeRatesController::class, 'index'])->name('currency_rates');
});
require __DIR__.'/auth.php';
