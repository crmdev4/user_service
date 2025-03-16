<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\UserAccount;
use App\Jobs\ImportEmployeesJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;

class EmployeesImport implements ToCollection
{
    protected $account, $importKey;

    public function __construct($account, $importKey)
    {
        $this->account = $account;
        $this->importKey = $importKey;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $dataWithoutHeader = $collection->slice(3);
        $mappedData = $dataWithoutHeader->map(function ($row) {
            \Log::info("Sending data : ");
            \Log::info(json_encode([
                'first_name' => strtolower($row[0]),
                'last_name' => strtolower($row[1]),
                'phone_number' => str_replace(["'", ' '], '', (string)$row[2]),
                'whatsapp_number' => str_replace(["'", ' '], '', (string)$row[3]),
                'email' => strtolower($row[4]),
                'company_id' => $this->account->secondary_id,
            ]));
            return [
                'first_name' => strtolower($row[0]),
                'last_name' => strtolower($row[1]),
                'phone_number' => str_replace(["'", ' '], '', (string)$row[2]),
                'whatsapp_number' => str_replace(["'", ' '], '', (string)$row[3]),
                'email' => strtolower($row[4]),
                'company_id' => $this->account->secondary_id,
            ];
        });

        $batches = $mappedData->chunk(50);
        $totalBatches = $batches->count();
        foreach ($batches as $batchNumber => $batch) {
            ImportEmployeesJob::dispatch($batch->toArray(), $batchNumber + 1, $totalBatches, $this->importKey);
        }
    }
}
