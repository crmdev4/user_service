<?php

namespace App\Http\Controllers\API\Auth;

use App\Mail\forgetPasswordMail;
use App\Models\EmailUserVerification;
use Carbon\Carbon;
use Mail;
use Str;
use URL;
use Validator;
use App\Models\User;
use App\Models\Account;
use App\Models\UserAccount;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use App\Http\Requests\EmployeeRegistrationRequest;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Http;

class AuthController extends BaseController
{
    use ApiResponseTrait;


    public function register(UserRequest $request)
    {
        $accountType = $request->header('x-account-type');
        if (empty($accountType)) {
            return $this->failedResponse(null, 'Account type is required');
        }
        $account = Account::where('account', $accountType)->first();

        if (empty($account)) {
            return $this->failedResponse(null, 'Registrasi tidak dapat diproses', 203);
        };

        $user = User::where('email', $request->email)->orWhere('phone', $request->phone)->first();

        if ($user) {
            $checkAccount = $this->checkAccount($user, $accountType);
            //dd($checkAccount);
            if ($checkAccount == true) {
                return $this->failedResponse(null, 'Akun sudah terdaftar silahkan login', 303);
            }

            return $this->successResponse(['status' => 302, 'data' => $user], 'User already register, please activate account', 302);
            //register another account
            //$registerAccount    = $this->registerAccount($user, $account, $request);

            /* return $this->successResponse(
                [
                    'user' => $user,
                    'account' => $registerAccount,
                    'redirect' => $registerAccount->host,
                ],
                'Account register success, signin to activation your account'
            ); */
        }

        DB::beginTransaction();
        try {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => 0,
                'banned' => 0,
                'last_login' => now(),
            ]);

            // $verificationUrl = URL::temporarySignedRoute(

            //     $expiration,
            //     [
            //         'token' => $token
            //     ]
            // );



            activity('Register User')
                ->causedBy($user)
                ->performedOn($user)
                ->log('Register User');

            $registerAccount = $this->registerAccount($user, $account, $request);

            DB::commit();
            return $this->successResponse(
                [
                    'user' => $user,
                    'account' => $registerAccount,
                    'redirect' => $registerAccount->host . '/unactivated',
                ],
                'User register success, signin to activation your account.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->failedResponse(null, $e->getMessage(), 203);
        }
    }

    public function activate_account(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'host' => 'required',
        ]);

        $accountType = $request->header('x-account-type');
        if (empty($accountType)) {
            return $this->failedResponse(null, 'Account type is required');
        }

        $user = User::find($request->user_id);
        $checkAccount = $this->checkAccount($user, $accountType);
        if ($checkAccount == true) {
            return $this->failedResponse(null, 'Akun sudah terdaftar silahkan login', 303);
        }
        $account = Account::where('account', $accountType)->first();

        if ($user) {
            $registerAccount = $this->registerAccount($user, $account, $request);


            $token = Str::random(60);
            $expiration = Carbon::now()->addMinutes(value: 60);

            EmailUserVerification::where('user_id', $user->id)->delete();
            EmailUserVerification::create([
                'user_id' => $user->id,
                'token' => hash('sha256', $token),
                'expired_at' => $expiration,
            ]);

            $response = Http::timeout(30)->post(config("apiendpoints.LOCAL_API_NOTIFICATION") . '/api/v1/email/send', [
                'action' => 'COMPANY_REGISTRATION_VERIFICATION',
                'email' => $user->email,
                'data' => [
                    'full_name' => $user->name,
                    'url_verification' => $registerAccount->host . "/activation/" . $token
                ]
            ]);

            return $this->successResponse(
                [
                    'user' => $user,
                    'account' => $registerAccount,
                    'redirect' => $registerAccount->host,
                ],
                'Account register success, signin to activation your account',
                201
            );
        } else {
            return $this->failedResponse(null, 'Registrasi tidak dapat diproses. Data tidak ditemukan', 404);
        }
    }

    private function checkAccount($user, $accountType)
    {
        $accounts = UserAccount::where('user_id', $user->id);
        $accounts = $accounts->select(
            'user_accounts.id as user_account_id',
            'user_accounts.user_id',
            'user_accounts.account_id',
            'user_accounts.host',

            'accounts.*',
        );
        $accounts = $accounts->leftJoin('accounts', 'user_accounts.account_id', '=', 'accounts.id');
        $itemsArray = $accounts->pluck('account')->toArray();

        $checkAccount = in_array($accountType, $itemsArray);

        return $checkAccount;
    }

    private function registerAccount($user, $account, $request)
    {
        $userAccount = UserAccount::where('user_id', $user->id)->where('account_id', $account->id)->exists();
        if ($userAccount == true) {
            return $this->failedResponse(null, 'Akun sudah terdaftar silahkan login', 303);
        }

        $host = 'https://' . $request->host . '.' . config('app.domain');

        $checkHost = UserAccount::where('host', $host)->exists();
        
        if ($checkHost == true) {
            // dd($checkHost);
            // return $this->failedResponse('Nama domain tidak tersedia', 303);
            return [
                'error' => true,
                'message' => 'Nama domain tidak tersedia',
                'status' => 303
            ];
        }

        DB::beginTransaction();
        try {
            $query = new UserAccount;
            $query->user_id = $user->id;
            $query->account_id = $account->id;
            if ($request->host) {
                $query->host = $host;
                // $query->host = $request->host;
                $query->is_subdomain = 1;
            }
            $query->is_activated = 0;

            $query->save();

            $result = $query->refresh();
            $token = Str::random(60);
            $expiration = Carbon::now()->addMinutes(value: 60);

            EmailUserVerification::create([
                'user_id' => $user->id,
                'token' => hash('sha256', $token),
                'expired_at' => $expiration,
            ]);

            DB::commit();

            /* $response = Http::timeout(30)->post(config("apiendpoints.LOCAL_API_NOTIFICATION") . '/api/v1/email/send', [
                'action' => 'COMPANY_REGISTRATION_VERIFICATION',
                'email' => $request->email,
                'data' => [
                    'full_name' => $request->name,
                    'url_verification' => $host . "/activation/" . $token
                ]
            ]); */

            activity('Register Account')
                ->causedBy($user)
                ->performedOn($user)
                ->log('Register account ' . $account->account);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->failedResponse(null, $e->getMessage(), 203);
        }
    }

    public function set_secondary_id(Request $request)
    {
        //$request->validate([ 'user_id' => 'required|exists:user_accounts,user_id', 'secondary_id' => 'required|string' ]);

        $accounts = UserAccount::where('user_id', $request->user_id)->update([
            'secondary_id' => $request->secondary_id
        ]);

        $account = UserAccount::where('user_id', $request->user_id)->where('secondary_id', $request->secondary_id)->first();

        return $this->successResponse(
            $account,
            'Update secondary id success'
        );
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);


        $accountType = $request->header('x-account-type');
        if (empty($accountType)) {
            return $this->failedResponse(null, 'Account type is required');
        }

        $forwardedHost = $request->header('x-forwarded-host');

        $grantHost = config('host.grant_host');


        // Determine if the login input is an email or a phone number
        $loginField = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$loginField => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            if ($user->banned) {
                return $this->failedResponse(null, 'Your account has been blocked, please contact administrator', 401);
            }

            // if ($user->is_active) {

            // update device and package id if exist
            if (!empty($request->device_id) && !empty($request->package_id)) {
                $user->device_id = $request->device_id;
                $user->package_id = $request->package_id;
                $user->save();
            }

            $account = UserAccount::where('user_id', $user->id);
            $account = $account->select(
                'user_accounts.secondary_id as secondary_id',
                'user_accounts.id as user_account_id',
                'user_accounts.user_id',
                'user_accounts.account_id',
                'user_accounts.host',
                'accounts.*',
            );
            $account = $account->leftJoin('accounts', 'user_accounts.account_id', '=', 'accounts.id');

            $itemsArray = $account->pluck('account')->toArray();
            $accountData = Account::select('is_single_device')->where('account', $accountType)->first();

            if ($accountData) {
                if ($accountData->is_single_device) {
                    $user->tokens()->delete();
                }
            } 

            if (in_array($accountType, $itemsArray)) {
                if ($accountType == 'fms_company' && !empty($forwardedHost)) {
                    $userAccount = UserAccount::where('user_id', $user->id)->first();
                    if (!in_array($forwardedHost, $grantHost)) {
                        if ($userAccount->host != $forwardedHost) {
                            return $this->failedResponse(null, 'Unauthorized.');
                        }
                    }
                    // Get role & permission
                    $userRolesAndPermissions = $this->getUserRolesAndPermissions($user);

                    // Success array
                    $success = [
                        'role' => $userRolesAndPermissions['role'],
                        'permission' => $userRolesAndPermissions['permission'],
                    ];
                }

                $success['token'] = $user->createToken('authToken', $itemsArray)->accessToken;
                $success['name'] = $user->name;
                $success['account'] = $itemsArray;
                $success['secondary_id'] = $account->where('account', $accountType)->select('secondary_id')->first()['secondary_id'];
                $success['user_id'] = $user->id;

                if ($accountType == 'fms_driver') {
                    $employeeId = UserAccount::where('user_id', $user->id)->where('account', 'fms_driver')->leftJoin('accounts', 'user_accounts.account_id', '=', 'accounts.id')->first();
                    $success['employee_id'] = $employeeId->secondary_id;
                }

                return $this->successResponse($success, 'User login successfully.');
            } else {
                return $this->failedResponse(null, 'Account type not found. Login failed', 401);
            }
            //}
            return $this->failedResponse(null, 'Unauthorized.');
        }
        return $this->failedResponse(null, 'Unauthorized.');
    }

    public function getUserRolesAndPermissions($user)
    {
        if (!$user) {
            throw new InvalidArgumentException("User object is required");
        }

        // Get permissions via roles
        $permissionsViaRoles = $user->getPermissionsViaRoles();

        // Get direct permissions
        $directPermissions = $user->getDirectPermissions();

        // Merge both collections and remove duplicates
        $mergedPermissions = $permissionsViaRoles->merge($directPermissions)->unique('id');

        // Extract permission names
        $permissionNames = $mergedPermissions->pluck('name');

        return [
            'role' => $user->getRoleNames(), // Role names
            'permission' => $permissionNames, // Permission names
        ];
    }

    public function user(Request $request)
    {
        $user = Auth::user();
        //$user = User::find(Auth::user()->id);
        $account = UserAccount::where('user_id', $user->id);
        $account = $account->select(
            'user_accounts.secondary_id as secondary_id',
            'user_accounts.id as user_account_id',
            'user_accounts.user_id',
            'user_accounts.account_id',
            'user_accounts.host',
            'accounts.*',
        );
        $account = $account->leftJoin('accounts', 'user_accounts.account_id', '=', 'accounts.id');
        $itemsArray = $account->pluck('account')->toArray();
        $user->account = $itemsArray;
        $user->tokens = $user->tokens;

        if ($user) {
            // Get role & permission
            $userRolesAndPermissions = $this->getUserRolesAndPermissions($user);

            // Success array
            $user->role = $userRolesAndPermissions['role'];
            $user->permission = $userRolesAndPermissions['permission'];

            return $this->successResponse($user, 'User data found.');
        }
        return $this->failedResponse(null, 'Unauthorized.');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $userAccount = UserAccount::where('user_id', $user->id)->exists();
        if ($userAccount == false) {
            return $this->failedResponse(null, 'Akun tidak terdaftar silahkan registrasi', 303);
        }

        DB::beginTransaction();
        try {
            $query = User::find($user->id);
            $query->name = $request->name;
            $query->phone = $request->phone;

            $query->save();
            $result = $query->refresh();

            DB::commit();

            activity('Update Account')
                ->causedBy($user)
                ->performedOn($user)
                ->log('Update account ' . $user->id);

            return $this->successResponse(
                $result,
                'Update account success'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->failedResponse(null, $e->getMessage(), 203);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return $this->successResponse(null, 'User logout successfuly.');
    }

    public function employeeRegistration(EmployeeRegistrationRequest $request)
    {
        // $gatewayUrl = env('API_URL') . '/employees/employee/getbykey';

        // for testing purposes only
        $localGatewayUrl = 'http://localhost.employees:5555/api/v1/employee/getbykey';

        $response = Http::post($localGatewayUrl, [
            'employee_id' => $request->employee_id_card,
            'mobile_phone' => $request->mobile_phone,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $employeeData = $data['data'];
            if ($employeeData['is_verified']) {
                $account = Account::where('account', 'fms_driver')->first();

                if (empty($account)) {
                    return $this->failedResponse(null, 'Registrasi tidak dapat diproses', 203);
                }

                DB::beginTransaction();
                try {
                    // Register the user
                    \Log::info("Email : " . $employeeData['addressContact']['personal_email']);
                    \Log::info("Phone : " . $employeeData['addressContact']['mobile_phone']);

                    // Cek Email
                    $user = User::where('email', $employeeData['addressContact']['personal_email'])->orWhere('phone', $employeeData['addressContact']['mobile_phone'])->first();

                    if (!$user || empty($user)) {
                        $user = User::create([
                            'name' => $employeeData['first_name'] . ' ' . $employeeData['last_name'],
                            'email' => $employeeData['addressContact']['personal_email'],
                            'phone' => $employeeData['addressContact']['mobile_phone'],
                            'password' => Hash::make($request->password),
                            'username' => $employeeData['addressContact']['personal_email'],
                            'is_active' => 1,
                            'banned' => 0,
                            'last_login' => now(),
                            'device_id' => $request->device_id,
                            'package_id' => $request->package_id
                        ]);
                    }

                    $checkAccount = $this->checkAccount($user, 'fms_driver');
                    \Log::info("Check Account : ");
                    \Log::info($checkAccount);
                    if ($checkAccount == true) {
                        return $this->failedResponse(null, 'Akun sudah terdaftar silahkan login.', 303);
                    }

                    activity('Register User')
                        ->causedBy($user)
                        ->performedOn($user)
                        ->log('Register User');

                    // Create the user account
                    $registerAccount = new UserAccount;
                    $registerAccount->user_id = $user->id;
                    $registerAccount->account_id = $account->id;
                    $registerAccount->secondary_id = $employeeData['id'];
                    $registerAccount->is_activated = 1;
                    $registerAccount->save();

                    activity('Register Account')
                        ->causedBy($user)
                        ->performedOn($user)
                        ->log('Register account ' . $account->account);

                    // Fetch the account information
                    $account = UserAccount::where('user_id', $user->id)
                        ->select(
                            'user_accounts.secondary_id as secondary_id',
                            'user_accounts.id as user_account_id',
                            'user_accounts.user_id',
                            'user_accounts.account_id',
                            'user_accounts.host',
                            'accounts.*'
                        )
                        ->leftJoin('accounts', 'user_accounts.account_id', '=', 'accounts.id');

                    $itemsArray = $account->pluck('account')->toArray();
                    $accountData = Account::select('is_single_device')->where('account', 'fms_driver')->first();

                    // Delete any existing tokens if single device login is enforced
                    if ($accountData->is_single_device) {
                        $user->tokens()->delete();
                    }

                    Auth::login($user);

                    $success['token'] = $user->createToken('authToken', $itemsArray)->accessToken;
                    $success['name'] = $user->name;
                    $success['account'] = $itemsArray;
                    $success['secondary_id'] = $account->where('account', 'fms_driver')->pluck('secondary_id')->toArray();
                    $success['user_id'] = $user->id;
                    $success['employee_id'] = $employeeData['id'];

                    DB::commit();

                    $response = Http::timeout(10)->post(config("apiendpoints.LOCAL_API_NOTIFICATION") . '/api/v1/email/send', [
                        'action' => 'EMPLOYEE_REGISTRATION_SUCCESS',
                        'data' => [
                            'email' => $employeeData['addressContact']['personal_email'],
                            'full_name' => $employeeData['first_name'] . ' ' . $employeeData['last_name'],
                            'mobile_phone' => $employeeData['addressContact']['mobile_phone'],
                            'employee_id' => $employeeData['employee_id']
                        ]
                    ]);

                    return $this->successResponse(
                        $success,
                        'User registered and logged in successfully'
                    );
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::info("Error message : ");
                    \Log::info($e->getMessage());
                    return $this->failedResponse(null, 'Registrasi tidak dapat diproses. Ada kendala', 500);
                }
            } else {
                return $this->failedResponse(null, 'Registrasi tidak dapat diproses. Data karyawan belum terverifikasi', 422);
            }
        } else {
            return $this->failedResponse(null, 'Registrasi tidak dapat diproses. Data tidak ditemukan', 404);
        }
    }

    public function resetPassword(Request $request)
    {
        // Validate the request body
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
            return $this->successResponse($user->tokens, 'Ubah password berhasil.');
        }
        return $this->failedResponse(null, 'Unauthorized.');
    }

    public function change_password(Request $request)
    {
        // Validate the request body
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->failedResponse(null, json_encode($validator->errors()));
        }

        $user = Auth::user();

        if ($user) {
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->failedResponse(null, 'Wrong password, Unauthorized.');
            }
            $user->password = Hash::make($request->password);
            $user->save();
            return $this->successResponse($user, 'Ubah password berhasil.');
        }
        return $this->failedResponse(null, 'Unauthorized.');
    }
}
