<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Services\User\UserService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\UserAccount;
use App\Models\Account;
use Validator;

class UserController extends Controller
{
    use ApiResponseTrait;

    protected $service;
    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {

        return $this->service->allData($request->all());
    }

    public function datatables(Request $request)
    {

        $draw = $request->input('draw');
        $company_id = $request->input('company_id');
        $offset = $request->input('start');
        if ($offset == '') {
            $offset = 0;
        }
        ;
        $limit = $request->input('length');
        if ($limit == '') {
            $limit = 25;
        }
        ;
        $search = $request->input('search')['value'] ?? '';
        $order = $request->input('order')[0]['column'] ?? '';
        $sort = $request->input('order')[0]['dir'] ?? 'DESC';
        $columns = $request->input('columns')[$order]['data'] ?? 'user_accounts.created_at';

        $query = UserAccount::query();

        $query = $query->leftJoin('users', 'user_accounts.user_id', '=', 'users.id');
        $query = $query->where('user_accounts.secondary_id', $company_id);
        $query = $query->whereNotNull('user_accounts.host');
        if ($search != '') {
            $query = $query->where('users.name', 'like', '%' . $search . '%');
        }

        $query = $query->orderBy($columns, $sort);
        $query = $query->offset($offset);
        $query = $query->limit($limit);
        $count = $query->count();
        $query = $query->get();

        foreach ($query as $key => $val) {
            $query[$key]     = $val;
            $query[$key]['role']    = User::find($val->id)->getRoleNames()->first();
        }

        $result['success'] = true;
        $result['draw'] = $draw;
        $result['recordsTotal'] = $count;
        $result['recordsFiltered'] = $count;
        $result['data'] = $query;

        return response()->json($result, 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'name' => 'required|string',
            'phone' => 'required|string',
            'role' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->failedResponse(null, json_encode($validator->errors()), 400, json_encode($validator->errors()));
        }

        $accountType = $request->header('x-account-type');
        if (empty($accountType)) {
            return $this->failedResponse(null, 'Account type is required');
        }

        $account = Account::where('account', $accountType)->first();

        if (empty($account)) {
            return $this->failedResponse(null, 'Registrasi tidak dapat diproses', 203);
        }
        ;

        $user = User::where('email', $request->email)->orWhere('phone', $request->phone)->first();

        if ($user) {
            $checkAccount = $this->checkAccount($user, $accountType);

            if ($checkAccount == true) {
                return $this->failedResponse(null, 'Akun sudah terdaftar silahkan login', 303);
            }

            //return $this->successResponse($user, 'User already register, please activate account', 302);
            //register another account
            $registerAccount = $this->registerAccount($user, $account, $request);

            return $this->successResponse(
                [
                    'user' => $user,
                    'account' => $registerAccount,
                ],
                'Account register success, signin to activation your account'
            );
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->email,
                'password' => '',
                'phone' => $request->phone,
                'is_active' => 1,
                'banned' => $request->is_banned,
                'last_login' => now(),
            ]);

            DB::commit();

            activity('Register User')
                ->causedBy($user)
                ->performedOn($user)
                ->log('Register User');

            $registerAccount = $this->registerAccount($user, $account, $request);
            return $this->successResponse(
                [
                    'user' => $user,
                    'account' => $registerAccount,
                ],
                'User register success, signin to activation your account',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->failedResponse(null, $e->getMessage(), 203);
        }
    }

    private function registerAccount($user, $account, $request)
    {
        $existAccount = UserAccount::where('user_id', $user->id)->where('account_id', $account->id)->exists();

        if ($existAccount == true) {
            return $this->failedResponse(null, 'User already register, please activate account', 303);
        }
        $existUser = Auth::user();
        $userHost = UserAccount::where('user_id', $existUser->id)->where('account_id', $account->id)->first();
        DB::beginTransaction();
        try {
            $query = new UserAccount;
            $query->user_id = $user->id;
            $query->account_id = $account->id;
            if ($userHost->host) {
                $query->host = $userHost->host;
                $query->is_subdomain = 1;
            }
            $query->is_activated = 1;
            $query->is_banned = $request->is_banned;
            $query->secondary_id = $request->company_id;

            $query->save();
            $result = $query->refresh();

            if (isset($user->getRoleNames()[0])) {
                $user->removeRole($user->getRoleNames()[0]);
            }
            $user->assignRole($request->role);

            DB::commit();

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

    public function checkAccountById($id)
    {
        if (empty($id)) {
            return $this->failedResponse(null, 'ID cannot empty');
        }

        $accounts = UserAccount::where('secondary_id', $id);
        $accounts = $accounts->select(
            'user_accounts.id as user_account_id',
            'user_accounts.user_id',
            'user_accounts.account_id',
            'users.*',
        );
        $accounts = $accounts->leftJoin('users', 'users.id', '=', 'user_accounts.user_id')->first();
        if ($accounts) {
            return $this->successResponse(
                $accounts,
                'User data found',
                200
            );
        }
        return $this->failedResponse(null, 'Data not found.');
    }

    public function deactivateAccountById($id)
    {
        if (empty($id)) {
            return $this->failedResponse(null, 'ID cannot empty');
        }

        $accounts = UserAccount::where('secondary_id', $id);
        $accounts = $accounts->select(
            'user_accounts.id as user_account_id',
            'user_accounts.user_id',
            'user_accounts.account_id',
            'users.*',
        );
        $accounts = $accounts->leftJoin('users', 'users.id', '=', 'user_accounts.user_id')->first();
        if (!$accounts) {
            return $this->failedResponse(null, 'Data not found.');
        }
        $updateUserAccount = UserAccount::where('secondary_id', $id)->update(['is_activated' => 0]);
        $updateUser = User::where('id', $accounts->user_id)->update(['is_active' => 0]);
    }

    public function reactivateAccountById($id)
    {
        if (empty($id)) {
            return $this->failedResponse(null, 'ID cannot empty');
        }

        $accounts = UserAccount::where('secondary_id', $id);
        $accounts = $accounts->select(
            'user_accounts.id as user_account_id',
            'user_accounts.user_id',
            'user_accounts.account_id',
            'users.*',
        );
        $accounts = $accounts->leftJoin('users', 'users.id', '=', 'user_accounts.user_id')->first();
        if (!$accounts) {
            return $this->failedResponse(null, 'Data not found.');
        }
        $updateUserAccount = UserAccount::where('secondary_id', $id)->update(['is_activated' => 1]);
        $updateUser = User::where('id', $accounts->user_id)->update(['is_active' => 1]);

    }

    public function updateAccountById($id, Request $request)
    {
        if (empty($id)) {
            return $this->failedResponse(null, 'Employee ID cannot empty');
        }

        $accounts = UserAccount::where('secondary_id', $id);
        $accounts = $accounts->select(
            'user_accounts.id as user_account_id',
            'user_accounts.user_id',
            'user_accounts.account_id',
            'users.*',
        );
        $accounts = $accounts->leftJoin('users', 'users.id', '=', 'user_accounts.user_id')->first();
        if (!$accounts) {
            return $this->failedResponse(null, 'Data not found.');
        }
        $updateUser = User::where('id', $accounts->user_id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $request->email,
        ]);
    }

    public function show($id)
    {
        //return $this->service->getData($id);
        $account = UserAccount::where('user_id', $id);
        $account = $account->select(
            'user_accounts.secondary_id as secondary_id',
            'user_accounts.id as user_account_id',
            'user_accounts.user_id',
            'user_accounts.account_id',
            'user_accounts.host',
            'user_accounts.is_banned',
            'accounts.id as account_id',
            'accounts.account',
            'users.*',
        );
        $account = $account->leftJoin('accounts', 'user_accounts.account_id', '=', 'accounts.id');
        $account = $account->leftJoin('users', 'user_accounts.user_id', '=', 'users.id');
        $itemsArray = $account->pluck('account')->toArray();
        //$user->account	= $itemsArray;
        $result['success'] = true;
        $result['data'] = $account->first();
        if ($result) {

            // Get role & permission
            $user = User::find($result['data']->id);

            // Initialize role in result array
            $result['role'] = null;

            // Check if user and roles exist
            if ($user && $user->roles && $user->roles->isNotEmpty()) {
                $result['role'] = $user->roles; // Assign roles to the result
            }

            return response()->json($result, 200);
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

    public function update(Request $request, $id)
    {

        $query = User::find($id);
        if (!$query) {
            return $this->failedResponse(null, 'User not found, please use valid account id', 404);
        }
        DB::beginTransaction();
        try {
            $query->name = $request->name;
            $query->phone = $request->phone;
            $query->email = $request->email;
            $query->save();
            $query = $query->refresh();

            if($request->role){
                $role  = Role::find($request->role);
                if (isset($query->getRoleNames()[0])) {
                    $query->removeRole($query->getRoleNames()[0]);
                }
                $query->assignRole($role->name);
            }

            DB::commit();

            $user = Auth::user();
            activity('Update User Account')
                ->causedBy($user)
                ->performedOn($user)
                ->log('Update user account ' . $query->name . 'to' . $request->name);

            return $this->successResponse(
                [
                    'user' => $user,
                ],
                'User update success',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->failedResponse(null, $e->getMessage(), 203);
        }
    }

    public function banned(Request $request, $id)
    {

        $query = UserAccount::where('user_id', $id)->first();
        if (!$query) {
            return $this->failedResponse(null, 'User not found, please use valid account id', 404);
        }
        DB::beginTransaction();
        try {
            $query->is_banned = $request->banned;
            $query->banned_reason = $request->banned_reason;

            $query->save();
            $query = $query->refresh();
            DB::commit();

            $user = Auth::user();
            activity('Update User Account')
                ->causedBy($user)
                ->performedOn($user)
                ->log('Update user account ' . $query->is_banned . 'to' . $request->banned);

            return $this->successResponse(
                [
                    'user' => $user,
                ],
                'User update success',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->failedResponse(null, $e->getMessage(), 203);
        }
    }

    public function getUserBannedInfo($id)
    {
        $account = UserAccount::where('secondary_id', $id);
        $account = $account->select(
            'user_accounts.secondary_id as secondary_id',
            'user_accounts.id as user_account_id',
            'user_accounts.user_id',
            'user_accounts.account_id',
            'user_accounts.is_banned',
            'accounts.id as account_id',
            'accounts.account',
            'users.banned',
            'users.banned_reason',
            'users.name',
        );
        $account = $account->leftJoin('accounts', 'user_accounts.account_id', '=', 'accounts.id');
        $account = $account->leftJoin('users', 'user_accounts.user_id', '=', 'users.id');
        $itemsArray = $account->pluck('account')->toArray();
        //$user->account	= $itemsArray;
        $result['success'] = true;
        $result['data'] = $account->first();
        if ($result) {
            return response()->json($result, 200);
        }
        return $this->failedResponse(null, 'Unauthorized.');
    }
}
