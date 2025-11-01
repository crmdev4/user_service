<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\forgetPasswordMail;
use App\Models\EmailForgotPassword;
use App\Models\UserAccount;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Hash;
use Http;
use Illuminate\Http\Request;
use Mail;
use Str;
use URL;
use Validator;
use Auth;
use App\Models\User;
use App\Jobs\SendUserEmailJob;

class EmailForgotPasswordController extends Controller
{
    use ApiResponseTrait;

    public function sendEmailForgotPassword(Request $request)
    {

        $accountType = $request->header('x-account-type');

        if (empty($accountType)) {
            return $this->failedResponse(null, 'Account type is required');
        }

        $forwardedHost = $request->header('x-forwarded-host');

        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->failedResponse(null, 'Email not found');
        }


        $token = Str::random(60);
        $expiration = Carbon::now()->addMinutes(60);
        // $recentRequest = EmailForgotPassword::where('user_id', $user->id)
        //     ->where('created_at', '>=', Carbon::now()->subMinutes(1))
        //     ->first();

        // if ($recentRequest) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'A password reset request has been made. Please wait 15 minutes before making a new request.',
        //     ], 429);
        // }

        if ($accountType == 'fms_company') {
            $userAccount = UserAccount::where('user_id', $user->id)
                ->whereHas('account', function ($query) use ($user) {
                    $query->where('account', 'fms_company');
                })->first();

                // Jika forwardedHost tidak sesuai dengan host di UserAccount
                if (!$userAccount) {
                    return $this->failedResponse(null, 'Email not found');
                }

                $host = 'https://dashboard.rentfms.com';

        } else if ($accountType == 'fms_driver') {
            $userAccount = UserAccount::where('user_id', $user->id)
                ->whereHas('account', function ($query) use ($user) {
                    $query->where('account', 'fms_driver');
                })->first();

                if (!$userAccount) { 
                    return $this->failedResponse(null, 'Account not found');
                }
                $host = 'https://app.rentfms.com';
        } else {
            return $this->failedResponse(null, 'Account type not match');
        }

        EmailForgotPassword::where('user_id', $user->id)->delete();

        EmailForgotPassword::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expired_at' => $expiration,
        ]);

        $data = [
            'title' => 'Reset Password Akun',
            'name' => $user->name,
            'to' => $user->email,
            'url' => $host . "/recovery_password/" . $token,
            'view' => 'emails.forgot-password',
        ];

        //create send email
        SendUserEmailJob::dispatch($data)
            ->onQueue('email-user');


        return response()->json([
            'success' => true,
            'message' => 'Email verification sent successfully',
            'result' => [
                'redirect' => $host . "/signin"
            ]
        ]);
    }

    public function verifyToken(Request $request)
    {
        $token = $request->token;
        $verification = EmailForgotPassword::where('token', hash('sha256', $token))
            ->where('expired_at', '>=', Carbon::now())
            ->first();
        if ($verification) {
            return $this->successResponse(
                [
                    'status' => 'success'
                ],
                'Token Valid'
            );

        } else {
            return $this->failedResponse(
                null,
                'Token tidak valid',
                401,
                'Token tidak valid'
            );
        }
    }

    public function verifyTokenEmployee(Request $request)
    {
        // $token = $request->token;
        // $verification = EmailForgotPassword::where('token', hash('sha256', $token))
        //     ->where('expired_at', '>=', Carbon::now())
        //     ->first();
        // if ($verification) {
        //     return $this->successResponse(
        //         [
        //             'status' => 'success'
        //         ],
        //         'Token Valid'
        //     );

        // } else {
        //     return $this->failedResponse(
        //         null,
        //         'Token expired / token',
        //         401,
        //         'Token expired / token'
        //     );
        // }
    }

    public function recoveryPassword(Request $request)
    {

        $accountType = $request->header('x-account-type');

        if (empty($accountType)) {
            return $this->failedResponse(null, 'Account type is required');
        }

        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($accountType == 'fms_company') {
            $host = 'https://dashboard.rentfms.com';
        } else if ($accountType == 'fms_driver') {
            $host = 'https://app.rentfms.com';
        } else {
            return $this->failedResponse(null, 'Account type not match');
        }


        $token = $request->token;

        $verification = EmailForgotPassword::where('token', hash('sha256', $token))
            ->where('expired_at', '>=', Carbon::now())
            ->first();

        if (!$verification) {
            return $this->failedResponse(null, 'Token sudah kadaluarsa.');
        }

        $user = User::find($verification->user_id);
        
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
            $verification->delete();
            return $this->successResponse(
                [
                    'user' => $user,
                    'redirect' => $host . "/signin"

                ],
                'Successfully changed the password.'
            );
        }

        return $this->failedResponse(null, 'Unauthorized.');
    }

    public function recoveryPasswordEmployee(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'token' => 'required',
        //     'password' => 'required|min:6|confirmed',
        // ]);

        // if ($validator->fails()) {
        //     return $this->failedResponse(null, $validator->errors()->first());
        // }

        // $token = $request->token;

        // $verification = EmailForgotPassword::where('token', hash('sha256', $token))
        //     ->where('expired_at', '>=', Carbon::now())
        //     ->first();

        // if (!$verification) {
        //     return $this->failedResponse(null, 'Token expired / token');
        // }

        // $employee_data = Http::timeout(30)->post(
        //     config('apiendpoints.LOCAL_API_EMPLOYEES') . '/api/v1/employee_address_contact/get-employee-by-id',
        //     [
        //         'employee_id' => $verification->user_id,
        //     ]
        // );

        // $employee_data = $employee_data->json();

        // $userAccount = UserAccount::where('secondary_id', $employee_data['data']['employee']['company_id'])->first();

        // $response = Http::timeout(30)->post(config("apiendpoints.LOCAL_API_NOTIFICATION") . '/api/v1/email/send', [
        //     'email' => $employee_data['data']['email'],
        //     'action' => "EMPLOYEE_RECOVERY_PASSWORD",
        //     'data' => [
        //         'full_name' => $employee_data['data']['employee']['first_name'] . ' ' . $employee_data['data']['employee']['last_name'],
        //         'url_verification' => $userAccount->host . "/signin"
        //     ]
        // ]);

        // return $this->successResponse(
        //     [
        //         'redirect' => $userAccount->host,
        //         'user' => $employee_data['data']
        //     ],
        //     'Ubah password berhasil.'
        // );
    }
}
