<?php

namespace App\Services\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationEmail;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Log;

class EmailConsumerService
{
    protected $connection;
    protected $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            'rabbitmq', // host
            5672,       // port
            'guest',    // username
            'guest'     // password
        );
        $this->channel = $this->connection->channel();
    }

    public function consume()
    {
        // Declare queues
        $this->channel->queue_declare('send_verification_email', false, true, false, false);
        $this->channel->queue_declare('user_registration', false, true, false, false);

        // Consume verification emails
        $this->channel->basic_consume(
            'send_verification_email',
            '',
            false,
            true,
            false,
            false,
            function (AMQPMessage $message) {
                $data = json_decode($message->getBody(), true);
                $this->handleVerificationEmail($data);
            }
        );

        // Consume user registration
        $this->channel->basic_consume(
            'user_registration',
            '',
            false,
            true,
            false,
            false,
            function (AMQPMessage $message) {
                $data = json_decode($message->getBody(), true);
                $this->handleUserRegistration($data);
            }
        );

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    protected function handleVerificationEmail($data)
    {
        try {
            Mail::to($data['email'])->send(new VerificationEmail($data['token']));
            Log::info('Verification email sent successfully', $data);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    protected function handleUserRegistration($data)
    {
        try {
            Mail::to($data['email'])->send(new WelcomeEmail($data));
            Log::info('Welcome email sent successfully', $data);
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
