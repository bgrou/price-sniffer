<?php

use App\Http\Controllers\ProductEntryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SheetImportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/upload-sheet', [SheetImportController::class, 'import'])->name('upload-sheet');
});

Route::middleware('auth')->group(function () {
    Route::get('/product-entries', [ProductEntryController::class, 'index']);
});

require __DIR__.'/auth.php';
