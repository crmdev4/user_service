<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQ\EmailConsumerService;
use Illuminate\Support\Facades\Log;

class ConsumeRabbitMQEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consume-emails';

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
        $this->info('Starting RabbitMQ email consumer...');

        try {
            $consumer = new EmailConsumerService();
            $consumer->consume();
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('RabbitMQ Consumer Error: ' . $e->getMessage());
        }
    }
}
