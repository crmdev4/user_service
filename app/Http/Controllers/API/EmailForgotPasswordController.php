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
        $recentRequest = EmailForgotPassword::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->first();

        if ($recentRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A password reset request has been made. Please wait 15 minutes before making a new request.',
            ], 429);
        }

        if ($accountType == 'fms_company') {
            $userAccount = UserAccount::where('user_id', $user->id)
                ->whereHas('account', function ($query) use ($user) {
                    $query->where('account', 'fms_company');
                })->first();

                // Jika forwardedHost tidak sesuai dengan host di UserAccount
                if ($userAccount->host === $forwardedHost) {
                    $host = $userAccount->host;
                } else {
                    return $this->failedResponse(null, 'Email not found');
                }

        } else if ($accountType == 'fms_driver') {
            $userAccount = UserAccount::where('user_id', $user->id)
                ->whereHas('account', function ($query) use ($user) {
                    $query->where('account', 'fms_driver');
                })->first();
            if ($userAccount) {
                $headers = [
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . config('apiendpoints.LOCAL_API_DRIVER_KEY')
                ];

                try{
                    $responseDataDriver = Http::withHeaders($headers)->get(config('apiendpoints.LOCAL_API_DRIVER') . '/api/v1/drivers/' . $userAccount->secondary_id);
                }catch (\Exception $err){
                     
                    return response()->json([
                        'success' => false,
                        'message' => "Service driver offline",
                    ], 500);
                };

                $dataDriver = $responseDataDriver->json();
                if ($dataDriver['success']) {
                    $company_id = $dataDriver['data']['company_id'];
                    
                    $userAccount = UserAccount::where('secondary_id', $company_id)
                            ->whereHas('account', function ($query) use ($user) {
                                $query->where('account', 'fms_company');
                            })->first();

                    
                    $host = $userAccount->host ?? url("/");
                   
                } else {
                    return $this->failedResponse(null, $dataDriver['message']);
                }
            } else {
                return $this->failedResponse(null, 'Account not found');
            }
        } else {
            return $this->failedResponse(null, 'Account type not match');
        }

        EmailForgotPassword::where('user_id', $user->id)->delete();

        EmailForgotPassword::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expired_at' => $expiration,
        ]);


        $response = Http::timeout(30)->post(config("apiendpoints.LOCAL_API_NOTIFICATION") . '/api/v1/email/send', [
            'email' => $request->email,
            'action' => "FORGOT_PASSWORD",
            'data' => [
                'full_name' => $user->name,
                'url_verification' => $host . "/recovery_password/" . $token
            ]
        ]);


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
        // $validator = Validator::make($request->all(), [
        //     'token' => 'required',
        //     'password' => 'required|min:6|confirmed',
        // ]);

        // if ($validator->fails()) {
        //     return $this->failedResponse(null, $validator->errors()->first());
        // }

        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);


        $token = $request->token;

        $verification = EmailForgotPassword::where('token', hash('sha256', $token))
            ->where('expired_at', '>=', Carbon::now())
            ->first();

        if (!$verification) {
            return $this->failedResponse(null, 'Token sudah kadaluarsa.');
        }

        $user = User::find($verification->user_id);

        $userAccount = UserAccount::where('user_id', $user->id)->first();

        // cek apakah dia user company
        if ($userAccount->account->account == 'hris_company' && $userAccount->host) {
            $host = $userAccount->host;
        } else {
            $headers = [
                'accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('apiendpoints.LOCAL_API_EMPLOYEES_KEY')
            ];

            $dataDriver = Http::withHeaders($headers)->get(config('apiendpoints.LOCAL_API_EMPLOYEES') . '/api/v1/employee/' . $userAccount->secondary_id);
            $dataDriver = $dataDriver->json();
            if ($dataDriver['success']) {
                $company_id = $dataDriver['data']['company_id'];
                $parent_company = isset($dataDriver['data']['parent_company']) ? $dataDriver['data']['parent_company'] : null;
                if ($parent_company != "" && $parent_company != null) {
                    $userAccount = UserAccount::where('secondary_id', $parent_company)
                        ->whereHas('account', function ($query) use ($user) {
                            $query->where('account', 'hris_company');
                        })->first();
                } else {
                    $userAccount = UserAccount::where('secondary_id', $company_id)
                        ->whereHas('account', function ($query) use ($user) {
                            $query->where('account', 'hris_company');
                        })->first();
                }
                if ($userAccount) {
                    $host = $userAccount->host . '/password-recovery-success';
                } else {
                    return $this->failedResponse(null, 'Host not found');
                }
            } else {
                return $this->failedResponse(null, $dataDriver['message']);
            }
        }

        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
            $verification->delete();
            return $this->successResponse(
                [
                    'redirect' => $host,
                    'user' => $user
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
