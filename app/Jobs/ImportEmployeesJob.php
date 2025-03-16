<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

class ImportEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employeesBatch;
    protected $batchNumber;
    protected $totalBatches;
    protected $importKey; // To track the progress

    /**
     * Create a new job instance.
     */
    public function __construct(array $employeesBatch, $batchNumber, $totalBatches, $importKey)
    {
        $this->employeesBatch = $employeesBatch;
        $this->batchNumber = $batchNumber;
        $this->totalBatches = $totalBatches;
        $this->importKey = $importKey;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apiUrl = 'http://localhost:3001/api/v1/leads/';
        foreach ($this->employeesBatch as $employee) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiUrl, $employee);
            \Log::info($response->body());

            if ($response->failed()) {
                Log::error('Failed to send employee data: ' . $response->body());
            }
        }
        $progress = ($this->batchNumber / $this->totalBatches) * 100;
        // Redis::set("import_progress_{$this->importKey}", $progress);
        Cache::put("import_employee_progress_{$this->importKey}", $progress);
    }
}
