<?php

namespace App\Helpers;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMq
{
    public static function sendToRabbitMq(string $message, string $queueName)
    {
        try {
            // Establish connection to RabbitMQ
            $connection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'rabbitmq'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASSWORD', 'guest')
            );

            $channel = $connection->channel();

            // Declare the queue
            $channel->queue_declare($queueName, false, true, false, false);

            // Create the message
            $msg = new AMQPMessage($message, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);

            // Publish the message to the queue
            $channel->basic_publish($msg, '', $queueName);

            Log::info("Message sent to RabbitMQ queue '{$queueName}': " . $message);

            // Close the channel and connection
            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            Log::error("Failed to send message to RabbitMQ: " . $e->getMessage());
        }
    }
}
