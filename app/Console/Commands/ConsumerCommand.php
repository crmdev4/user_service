<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Mail\VerificationEmail;
use App\Mail\WelcomeEmail;
use App\Jobs\SendEmployeeVerificationEmailJob;
use App\Models\EmailUserVerification;
use App\Models\EmailVerification;
use Carbon\Carbon;

class ConsumerCommand extends Command
{
    protected $signature = 'rabbitmq:consumer';
    protected $description = 'RabbitMQ Consumer';

    public function handle()
    {
        try {
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();

            $channel->queue_declare(
                'default',
                false,
                true,
                false,
                false
            );

            $this->info(" [*] Waiting for messages. To exit press CTRL+C");

            $callback = function ($msg) {
                try {
                    $data = json_decode($msg->body, true);
                    $this->info(" [x] Received message: " . $msg->body);

                    if (isset($data['type'])) {
                        switch ($data['type']) {
                            case 'verification':
                                // Mail::to($data['email'])->send(new VerificationEmail($data['token']));
                                // dispatch job

                                // create token with hash sha256 from "id":"21b02abb-7d9c-4597-bb9c-07ff7e20ff8b","CompanyId":"34324-4dasfkf-2314-dsar5353"
                                $token = hash('sha256', $data['id'] . $data['company_id']);

                                // store to EmailUserVerification table
                                $verification = EmailVerification::create([
                                    'employee_id' => $data['id'],
                                    'company_id' => $data['company_id'],
                                    'token' => $token,
                                    'expired_at' => Carbon::now()->addDay(1),
                                ]);

                                $verificationUrl = 'http://auth.rentfms.test/api/users/verification-email-user?token=' . $token;

                                /* SendEmployeeVerificationEmailJob::dispatch($data, $verificationUrl); */
                                Mail::to($data['email'])->send(new VerificationEmail($token, $verificationUrl, $data));

                                Log::info(" [x] Verification email sent to: " . $data['email']);
                                $this->info(" [x] Verification email sent to: " . $data['email']);
                                break;
                            case 'registration':
                                Mail::to($data['email'])->send(new WelcomeEmail($data));
                                Log::info(" [x] Welcome email sent to: " . $data['email']);
                                $this->info(" [x] Welcome email sent to: " . $data['email']);
                                break;
                            default:
                                $this->warn(" [x] Unknown message type: " . $data['type']);
                        }
                    }

                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                } catch (\Exception $e) {
                    Log::error('Failed to process message: ' . $e->getMessage());
                    $this->error(" [x] Error: " . $e->getMessage());
                    $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
                }
            };

            $channel->basic_qos(null, 1, null);
            $channel->basic_consume('default', '', false, false, false, false, $callback);

            while ($channel->is_consuming()) {
                $channel->wait();
            }

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            Log::error('Consumer error: ' . $e->getMessage());
            $this->error(" [x] Consumer error: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
