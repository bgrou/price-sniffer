<?php

namespace App\Http\Controllers;

use App\Http\Requests\SheetImportRequest;
use App\Jobs\ProcessSheetImport;
use App\Models\FileUpload;
use App\Services\SheetImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class SheetImportController extends Controller
{
    public function __construct(private SheetImportService $service){}
    public function import(SheetImportRequest $request)
    {
        $uploadedFiles = [];
        $fileCount = count($request->file('files'));
        $userId = Auth::id();
        
        Log::info("Starting import of {$fileCount} files");
        
        // Process each file with a delay to prevent worker overload
        foreach ($request->file('files') as $index => $file) {
            try {
                $originalName = $file->getClientOriginalName();
                $filePath = $file->storeAs('xlsx_uploads', time() . '_' . $originalName, 'public');
                
                // Create a record in the database for this file upload
                $fileUpload = FileUpload::create([
                    'original_name' => $originalName,
                    'file_path' => $filePath,
                    'status' => 'queued',
                    'progress' => 0,
                    'user_id' => $userId,
                ]);
                
                // Add delay between job dispatches to prevent overloading the queue
                $delay = $index * 10; // 10 seconds between each job
                
                ProcessSheetImport::dispatch($filePath, $fileUpload->id)
                    ->delay(now()->addSeconds($delay))
                    ->onQueue('imports');
                
                $uploadedFiles[] = $fileUpload->id;
                
                Log::info("File queued: {$originalName} with {$delay}s delay, ID: {$fileUpload->id}");
            } catch (\Exception $e) {
                Log::error("Failed to queue file {$file->getClientOriginalName()}: {$e->getMessage()}");
            }
        }
        
        // Store uploaded file IDs in session for status tracking
        $request->session()->put('processing_file_ids', $uploadedFiles);
        
        return Redirect::back()
            ->with('success', count($uploadedFiles) . ' files queued for processing. Check the status below.')
            ->with('processing', true);
    }
    
    /**
     * Get the status of processing files
     */
    public function status(): JsonResponse
    {
        $fileIds = session('processing_file_ids', []);
        
        // Get the actual status from the database
        $files = [];
        
        if (!empty($fileIds)) {
            $fileUploads = FileUpload::whereIn('id', $fileIds)->get();
            
            foreach ($fileUploads as $fileUpload) {
                $files[] = [
                    'id' => $fileUpload->id,
                    'name' => $fileUpload->original_name,
                    'status' => $fileUpload->status,
                    'progress' => $fileUpload->progress,
                    'error' => $fileUpload->error_message,
                ];
            }
        }
        
        return response()->json($files);
    }

    public function truncate(): \Illuminate\Http\RedirectResponse
    {
        $this->service->truncate();
        return Redirect::back()->with('success', 'All sheets have been deleted.');
    }
}
