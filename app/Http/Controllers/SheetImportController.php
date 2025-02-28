<?php

namespace App\Http\Controllers;

use App\Http\Requests\SheetImportRequest;
use App\Jobs\ProcessSheetImport;
use App\Services\SheetImportService;
use Illuminate\Support\Facades\Redirect;

class SheetImportController extends Controller
{
    public function __construct(private SheetImportService $service){}
    public function import(SheetImportRequest $request): \Illuminate\Http\RedirectResponse
    {
        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $filePath = $file->storeAs('xlsx_uploads', $originalName, 'public');
            ProcessSheetImport::dispatch($filePath);
        }

        return Redirect::back()->with('success', 'Excel files are being processed in the background.');

    }

    public function truncate(): \Illuminate\Http\RedirectResponse
    {
        $this->service->truncate();
        return Redirect::back()->with('success', 'All sheets have been deleted.');
    }
}
