<?php

namespace App\Services\Auth;

use App\Filters\Auth\RoleFilter;
use App\Repositories\Auth\RoleRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Auth\PermissionRepository;
use App\Services\BaseService;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RoleService extends BaseService
{
    public function __construct(
        RoleRepository $repo,
        PermissionRepository $permissionrepo,
        protected UserRepository $userRepo,
        RoleFilter $filterClass
    ) {
        $this->repo = $repo;
        $this->permissionrepo = $permissionrepo;
        $this->userRepo = $userRepo;
        $this->filterClass = $filterClass;
    }

    public function getDatatables(array $request)
    {
        $draw = $request['draw'] ?? null;
        $company_id = $request['company_id'] ?? null;
        $offset = $request['start'] ?? 0;
        $limit = $request['length'] ?? 25;
        $search = $request['search']['value'] ?? '';
        $order = $request['order'][0]['column'] ?? '';
        $sort = $request['order'][0]['dir'] ?? 'DESC';
        $columns = $request['columns'][$order]['data'] ?? 'created_at';
        
        $user = Auth::user();
        $roleName = $user->getRoleNames()[0];
        //dd($roleName);
        $query = Role::query();

        // Filter where company_id is NULL or matches the given company_id
        if($roleName != 'Administrator Pro'){
            $query->where(function ($q) use ($roleName) {
                //$q->whereNull('company_id');
                $q->where('name', $roleName);
            });
        }else{
            $query = $query->whereNull('company_id');
        }
        //$query = $query->whereNull('company_id');
		
		if($company_id !== null){
            $query =   $query->orWhere('company_id', $company_id);
        }

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $query->orderBy($columns, $sort)
            ->offset($offset)
            ->limit($limit);

        $count = $query->count();
        $data = $query->get();

        $result = [
            'success' => true,
            'draw' => $draw,
            'recordsTotal' => $count,
            'recordsFiltered' => $count,
            'data' => $data,
        ];


        return $this->successResponseData($result, __('Role data found'));
    }

    public function store(array $request)
    {
        DB::beginTransaction();
        try {
            $uniqueName = $this->generateUniqueName($request['name'], $request['company_id']);
            $data = [
            'name' => $uniqueName,
            'guard_name' => $request['guard_name'],
            'company_id' => $request['company_id'],
            ];

            $role = $this->repo->create($data);
            DB::commit();
            $result['data'] = $role;
            return $this->successResponseData($result, __('Role created successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse(null, $e->getMessage(), 403);
        }
    }

    private function generateUniqueName(string $name, string $companyId): string
    {
        // Extract the first segment of the company_id (up to the first hyphen)
        $firstSegment = explode('-', $companyId)[0];
        return $firstSegment . '-' . $name;
    }

    public function getData($id)
    {
        $role = $this->repo->find($id);
        $role->getAllPermissions();
        $user = Auth::user();
        
        $result['data'] = [
            'role' => $role,
            //'all_permission' => $this->permissionrepo->getOrderBy('name', 'ASC', [])
            'all_permission' => $user->getPermissionsViaRoles()
            ];
        if ($role) {
            return $this->successResponseData($result, __('Role data found'));
        }
        return $this->failedResponse(null, __('Role not found'), 404);
    }

    public function update(array $request, $id)
    {
        DB::beginTransaction();
        try {
            $role = $this->repo->find($id);
            $uniqueName = $this->generateUniqueName($request['name'], $request['company_id']);
            $data = [
            'name' => $uniqueName,
            'guard_name' => $request['guard_name'],
            'company_id' => $request['company_id'],
            ];

            if ($role) {
                $role->update($data);
                $result['data'] = $role;
                DB::commit();
                return $this->successResponseData($result, __('Role updated successfully'));
            }
            return $this->failedResponse(null, __('Role not found'), 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse(null, $e->getMessage(), $e->getCode());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $role = $this->repo->find($id);
            if ($role) {
                $role->getAllPermissions();
                foreach($role['permissions'] as $item){
                    $role->revokePermissionTo($item['name']);
                }
                $role->delete();

                DB::commit();
                return $this->successResponseData(null, __('Role delete successfully'));
            }
            return $this->failedResponse(null, __('Role not found'), 404);
        } catch (\Exception $e) {
            DB::rollBack();
             
            return $this->failedResponse(null, $e->getMessage(), 403);
        }
    }

    public function assignUserToRole(array $request)
    {
        try {
            $user = $this->userRepo->find($request['user_id']);
            if (!$user) {
                return $this->failedResponse(null, __('User not found'), 404);
            }

            if (isset($user->getRoleNames()[0])) {
                $user->removeRole($user->getRoleNames()[0]);
            }
            
            $roleName = $request['role'];
            $user->assignRole($roleName);

            $result['data'] = $user;
            return $this->successResponseData($result, __('Role updated successfully'));
        } catch (\Exception $e) {
            return $this->failedResponse(null, __('Role updated failed'), 422);
        }
    }

    public function removeUserFromRole(array $request)
    {
        try {
            $user = $this->userRepo->find($request['user_id']);
            if (!$user) {
                return $this->failedResponse(null, _('User not found'), 404);
            }

            $roleName = $request['role'];
            $user->removeRole($roleName);
            return $this->successResponseData(null, __('Role updated successfully'));
        } catch (\Exception $e) {
            return $this->failedResponse(null, $e->getMessage(), $e->getCode());
        }
    }
}
