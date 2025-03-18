<?php

namespace App\Services;

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
        try {
            $this->connection = new AMQPStreamConnection(
                'rabbitmq',
                5672,
                'guest',
                'guest'
            );
            $this->channel = $this->connection->channel();
            Log::info('Successfully connected to RabbitMQ');
        } catch (\Exception $e) {
            Log::error('Failed to connect to RabbitMQ: ' . $e->getMessage());
            throw $e;
        }
    }

    public function consume()
    {
        try {
            // Declare queue with proper settings
            $this->channel->queue_declare('default', false, true, false, false);

            // Set QoS settings
            $this->channel->basic_qos(null, 1, null);

            // Single consumer for both types of messages
            $this->channel->basic_consume(
                'default',
                '',
                false,   // no local
                false,   // no ack changed to false for manual acknowledgment
                false,   // exclusive
                false,   // no wait
                function (AMQPMessage $message) {
                    try {
                        $data = json_decode($message->getBody(), true);
                        Log::info('Received message:', ['data' => $data]);

                        if (isset($data['type'])) {
                            switch ($data['type']) {
                                case 'verification':
                                    $this->handleVerificationEmail($data);
                                    break;
                                case 'registration':
                                    $this->handleUserRegistration($data);
                                    break;
                                default:
                                    Log::warning('Unknown message type', ['data' => $data]);
                            }
                        } else {
                            Log::warning('Message type not specified', ['data' => $data]);
                        }

                        // Acknowledge message after successful processing
                        $message->ack();
                        Log::info('Message processed and acknowledged');

                    } catch (\Exception $e) {
                        // Reject message on error
                        $message->reject(false);
                        Log::error('Error processing message: ' . $e->getMessage(), [
                            'data' => $data ?? null,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            );

            Log::info('Started consuming messages from RabbitMQ');

            while ($this->channel->is_consuming()) {
                try {
                    $this->channel->wait();
                } catch (\Exception $e) {
                    Log::error('Error while waiting for message: ' . $e->getMessage());
                    // Reconnect if connection is lost
                    $this->reconnect();
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in consume method: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function reconnect()
    {
        try {
            if ($this->channel) {
                $this->channel->close();
            }
            if ($this->connection) {
                $this->connection->close();
            }

            $this->connection = new AMQPStreamConnection(
                'rabbitmq',
                5672,
                'guest',
                'guest',
                '/',         // vhost
                false,      // insist
                'AMQPLAIN', // login method
                null,       // login response
                'en_US',    // locale
                3.0,        // connection timeout
                3.0,        // read write timeout
                null,       // context
                false,      // keepalive
                0          // heartbeat
            );
            $this->channel = $this->connection->channel();
            Log::info('Successfully reconnected to RabbitMQ');
        } catch (\Exception $e) {
            Log::error('Failed to reconnect to RabbitMQ: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function handleVerificationEmail($data)
    {
        try {
            Log::info('Processing verification email', $data);
            if (!isset($data['email']) || !isset($data['token'])) {
                Log::error('Missing required fields in verification data', $data);
                return;
            }
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
