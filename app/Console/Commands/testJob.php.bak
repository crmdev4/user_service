<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class testJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Log::info("test command berhasil");
        \App\Jobs\testingJob::dispatch();
    }
}
