<?php

namespace App\Imports;

use App\Models\UserAccount;
use App\Jobs\ImportEmployeesJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;

class EmployeesImport implements ToCollection, WithChunkReading, WithStartRow
{
    protected $account, $importKey;

    public function __construct($account, $importKey)
    {
        $this->account = $account;
        $this->importKey = $importKey;
        // Initialize batch counter
        Cache::put("import_employee_total_batches_{$importKey}", 0);
    }

    /**
     * Skip the first 3 rows (header rows)
     */
    public function startRow(): int
    {
        return 4; // Skip first 3 rows (0-indexed, so row 4 = index 3)
    }

    /**
     * Process data in chunks to avoid memory issues
     */
    public function chunkSize(): int
    {
        return 100; // Process 100 rows at a time
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        // Skip header rows if they exist in this chunk
        $dataWithoutHeader = $collection->filter(function ($row, $index) {
            // Skip empty rows
            return !empty(array_filter($row->toArray()));
        });

        if ($dataWithoutHeader->isEmpty()) {
            return;
        }

        $mappedData = $dataWithoutHeader->map(function ($row) {
            // Skip if row doesn't have enough columns
            if (count($row) < 5) {
                return null;
            }

            return [
                'first_name' => strtolower($row[0] ?? ''),
                'last_name' => strtolower($row[1] ?? ''),
                'phone_number' => str_replace(["'", ' '], '', (string)($row[2] ?? '')),
                'whatsapp_number' => str_replace(["'", ' '], '', (string)($row[3] ?? '')),
                'email' => strtolower($row[4] ?? ''),
                'company_id' => $this->account->secondary_id,
            ];
        })->filter(); // Remove null entries

        if ($mappedData->isEmpty()) {
            return;
        }

        // Process in smaller batches for job dispatch
        $batches = $mappedData->chunk(50);
        
        foreach ($batches as $batchNumber => $batch) {
            // Increment total batches counter
            $totalBatches = Cache::increment("import_employee_total_batches_{$this->importKey}");
            ImportEmployeesJob::dispatch($batch->toArray(), $totalBatches, $totalBatches, $this->importKey);
        }
    }
}
