<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearImportCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-import {--import-key= : Specific import key to clear} {--all : Clear all import caches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear import employee cache (progress and total batches)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            // Clear all import caches
            $this->info('Clearing all import caches...');
            
            // Get all cache keys (this is a workaround since Laravel doesn't have a direct way to list all keys)
            // We'll use a pattern-based approach if using Redis
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                $redis = Cache::getStore()->getRedis();
                $keys = $redis->keys('*import_employee*');
                
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        // Remove prefix if exists (Laravel adds prefix to cache keys)
                        $cleanKey = str_replace(config('cache.prefix') . ':', '', $key);
                        Cache::forget($cleanKey);
                        $this->line("Cleared: {$cleanKey}");
                    }
                    $this->info('All import caches cleared successfully!');
                } else {
                    $this->info('No import caches found.');
                }
            } else {
                // For other cache drivers, we can't easily list all keys
                // So we'll just inform the user
                $this->warn('Cannot list all cache keys for non-Redis drivers.');
                $this->info('Please use --import-key option to clear specific cache.');
            }
        } elseif ($this->option('import-key')) {
            $importKey = $this->option('import-key');
            $this->info("Clearing cache for import key: {$importKey}");
            
            $progressKey = "import_employee_progress_{$importKey}";
            $totalBatchesKey = "import_employee_total_batches_{$importKey}";
            
            Cache::forget($progressKey);
            Cache::forget($totalBatchesKey);
            
            $this->info("Cleared cache for import key: {$importKey}");
            $this->line("  - Progress cache cleared");
            $this->line("  - Total batches cache cleared");
        } else {
            $this->error('Please specify --import-key or --all option');
            $this->line('');
            $this->line('Usage examples:');
            $this->line('  php artisan cache:clear-import --import-key=uuid-here');
            $this->line('  php artisan cache:clear-import --all');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

