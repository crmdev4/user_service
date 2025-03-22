<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmailUserVerification;
use App\Models\EmailVerification;
use App\Models\UserAccount;
use App\Traits\ApiResponseTrait;
use App\Helpers\RabbitMq;
use Cache;
use Carbon\Carbon;
use Http;
use Illuminate\Http\Request;
use App\Models\User;
use Str;
use Validator;

class UserVerificationController extends Controller
{
    use ApiResponseTrait;

    public function verifyUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->failedResponse(
                null,
                $validator->errors()->first(),
                400,
                $validator->errors()
            );
        }

        $token = $request->token;

        $verification = EmailUserVerification::where('token', hash('sha256', $token))
            // ->where('expired_at', '>=', Carbon::now())
            ->first();

        if ($verification) {
            if ($verification->expired_at < Carbon::now()) {
                return $this->failedResponse(
                    null,
                    'Token has expired',
                    401,
                    'Token has expired'
                );
            } else {
                $user = User::where('id', $verification->user_id)->first();
                $userAccount = UserAccount::where('user_id', $user->id)->first();
                if ($user) {
                    $user->update(['email_verified_at' => date('Y-m-d'), 'is_active' => 1]);
                    $userAccount->update(['is_subdomain' => 1, 'is_activated' => 1]);
                    $verification->delete();

                    $cacheKey = "host_check_{$userAccount->host}";
                    Cache::put($cacheKey, [
                        'is_allowed' => true,
                        'is_activated' => true,
                    ], now()->addDays(7));


                    return $this->successResponse(
                        [
                            'status' => 'success',
                            'redirect' => $userAccount->host,
                        ],
                        'User verified',
                        200
                    );
                } else {
                    // return redirect($userAccount->host . '?success=false&message=Registrasi tidak dapat diproses. Data tidak ditemukan.');
                    return $this->failedResponse(
                        null,
                        'Registration cannot be processed due to data not found.',
                        400,
                        'Registration cannot be processed due to data not found.'
                    );
                }
            }
        }
        return $this->failedResponse(
            null,
            'Token not found',
            404,
            'Token not found'
        );
    }

    // Verifikasi ketersediaan host
    public function checkExistingHost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'host' => 'required|regex:/^[a-z0-9.-]+$/',
        ], [
            'host.required' => 'Host cannot be empty',
            'host.regex' => 'Host can only be lowercase letters, numbers, - and .',
        ]);

        if ($validator->fails()) {
            return $this->failedResponse(
                null,
                $validator->errors()->first(),
                400,
                $validator->errors()->first()
            );
        }

        $host = 'https://' . $request->host . "." . config('app.domain_hrms');

        $userAccount = UserAccount::where('host', $host)->first();

        $grantHost = [
            'https://launch.hrms.duluin.com',
            'https://dev.hrms.duluin.com',
            'http://hris.test',
            'http://hrms-dev.duluin.com',
            'http://devhris.duluin.com',
            'http://127.0.0.1:8000',
            'http://127.0.0.1',
            'http://duluin-hrms.test',
            'https://hris.test',
            'https://hrms-dev.duluin.com',
            'https://devhris.duluin.com',
            'https://127.0.0.1:8000',
            'https://127.0.0.1',
            'https://duluin-hrms.test',
            'https://api.duluin.com',
            'https://apis3.hrms.duluin.com',
            'https://db01.hrms.duluin.com',
            'https://notification.hrms.duluin.com',
            'https://npm01.hrms.duluin.com',
            'https://port01.hrms.duluin.com',
            'https://s3.hrms.duluin.com',
            'https://web.hrms.duluin.com',
            'https://apidev.hrms.duluin.com',
            'https://dev.hrms.duluin.com',
            'https://devapi3.hrms.duluin.com',
            'https://devhris.hrms.duluin.com',
            'https://launch.duluin.id',
            'https://notification.duluin.com',
            'https://project.duluin.com',
        ];

        if ($userAccount || in_array($host, $grantHost)) {
            return $this->failedResponse(
                null,
                'Host already in use',
                400,
                'Host already in use'
            );
        } else {
            return $this->successResponse(
                [
                    'status' => 'success',
                    'message' => 'Host available',
                    'data' => $host,
                ],
                'Host available',
                200
            );
        }
    }

    // resend email verification
    public function resendVerificationEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'host' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->failedResponse(
                null,
                $validator->errors()->first(),
                400,
                $validator->errors()
            );
        }

        $userAccount = UserAccount::where('host', $request->host)->first();

        if ($userAccount) {
            $user = User::where('id', $userAccount->user_id)->first();
            $emailVerification = EmailUserVerification::where('user_id', $user->id)->first();

            if ($emailVerification) {
                if ($emailVerification->expired_at < Carbon::now()) {
                    $emailVerification->delete();
                    $token = Str::random(60);
                    $expiration = Carbon::now()->addHours(24);
                    EmailUserVerification::create([
                        'user_id' => $user->id,
                        'token' => hash('sha256', $token),
                        'expired_at' => $expiration,
                    ]);
                    // send email
                    $response = Http::timeout(30)->post(config("apiendpoints.LOCAL_API_NOTIFICATION") . '/api/v1/email/send', [
                        'action' => 'COMPANY_REGISTRATION_VERIFICATION',
                        'email' => $user->email,
                        'data' => [
                            'full_name' => $user->name,
                            'url_verification' => $userAccount->host . "/activation/" . $token
                        ]
                    ]);

                    return $this->successResponse(
                        [
                            'status' => 'success',
                            'message' => 'Verification email sent successfully',
                        ],
                        'Verification email sent successfully',
                        201
                    );
                } else {
                    return $this->failedResponse(
                        null,
                        'Please check your email for verification',
                        200,
                        'Please check your email for verification'
                    );
                }
            } else {
                return $this->failedResponse(
                    null,
                    'Please register first',
                    404,
                    'Please register first'
                );
            }
        } else {
            return $this->failedResponse(
                null,
                'User not found',
                404,
                'User not found'
            );
        }
    }

    // verify token received from email
    public function verify(Request $request, $token)
    {
        $verification = EmailVerification::where('token', $token)->first();

        if ($verification) {
            if ($verification->expired_at < Carbon::now()) {
                \Log::info("Verification token: " . $token);
                \Log::info("Token has expired");
                // return redirect()->to(config('app.frontend_url') . '/verify-email?status=error&message=Token has expired');
            } else {
                // change leads status in leads service through rabbitMq
                \Log::info("Verification token: " . $token);
                \Log::info("Verification Data : " . json_encode($verification));

                // send to message broker (RabbitMQ, PORT:5672)
                $data = [
                    'type' => 'registration',
                    'lead_id' => $verification->employee_id,
                    'company_id' => $verification->company_id,
                ];
                \Log::info("Data to send to RabbitMQ : " . json_encode($data));

                RabbitMq::sendToRabbitMq(json_encode($data), 'default');
            }
        }
        // return redirect()->to(config('app.frontend_url') . '/verify-email?status=error&message=Token not found');
    }
}
