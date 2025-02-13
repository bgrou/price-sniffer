<?php
namespace App\Jobs;

use AllowDynamicProperties;
use App\Services\SheetImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PhpOffice\PhpSpreadsheet\Exception;


#[AllowDynamicProperties]
class ProcessSheetImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $filePath;

    /**
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @throws Exception
     * @throws ConnectionException
     */
    public function handle(SheetImportService $service): void
    {
        // Get the full path of the stored file
        $fullPath = storage_path("app/public/" . $this->filePath);
        $sheetName = basename($fullPath);
        $currency_key = $service->hasCurrencyFormatInSheet($fullPath);
        $objSheetRows = $service->xlsxToObj($fullPath);
        $service->store($sheetName, $objSheetRows, $currency_key);
    }
}
