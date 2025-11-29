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
        $apiUrl = 'http://service_driver:3001/api/v1/leads/';
        foreach ($this->employeesBatch as $employee) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiUrl, $employee);
            Log::info($response->body());

            if ($response->failed()) {
                Log::error('Failed to send employee data: ' . $response->body());
            }
        }
        // Get total batches from cache (may still be incrementing)
        $totalBatches = Cache::get("import_employee_total_batches_{$this->importKey}", $this->totalBatches);
        
        // Calculate progress, but don't exceed 100% until all batches are done
        $progress = min(($this->batchNumber / max($totalBatches, $this->totalBatches)) * 100, 99);
        
        // If this is the last known batch and total matches, set to 100%
        if ($this->batchNumber >= $totalBatches && $this->batchNumber == $this->totalBatches) {
            $progress = 100;
        }
        
        Cache::put("import_employee_progress_{$this->importKey}", $progress);
    }
}
