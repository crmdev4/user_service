<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailConsumerService;

class ProcessRabbitMqQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process RabbitMQ quueue messages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing RabbitMQ queue...');

        try {
            $consumer = new EmailConsumerService();
            $consumer->consume();
        } catch (\Exception $e) {
            $this->error('Queue processing error: ' . $e->getMessage());
        }
    }
}
