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
        $dataWithoutHeader = $collection->slice(4);
        $mappedData = $dataWithoutHeader->map(function ($row) {
            return [
                'employee_card_id' => (string)$row[0],
                'first_name' => ucfirst($row[1]),
                'last_name' => ucfirst($row[2]),
                'gender' => strtolower($row[3]),
                'date_of_birth' => $row[4] ?? Carbon::now()->format('Y-m-d'),
                'salutation' => strtolower((string)$row[9]),
                'date_of_joining' => $row[7] ?? Carbon::now()->format('Y-m-d'),
                'status' => strtolower($row[8]),
                'company_id' => $this->account->secondary_id,
                'phone_number' => str_replace(["'", ' '], '', (string)$row[5]),
                'personal_email' => strtolower($row[6]),
                'company_code' => strtoupper((string)$row[10]),
            ];
        });

        $batches = $mappedData->chunk(50);
        $totalBatches = $batches->count();
        foreach ($batches as $batchNumber => $batch) {
            ImportEmployeesJob::dispatch($batch->toArray(), $batchNumber + 1, $totalBatches, $this->importKey);
        }
    }
}
