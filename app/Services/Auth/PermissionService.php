<?php

namespace App\Services\Auth;

use App\Filters\Auth\PermissionFilter;
use App\Repositories\Auth\PermissionRepository;
use App\Repositories\Auth\RoleRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class PermissionService extends BaseService
{
    public function __construct(
        PermissionRepository $repo,
        protected RoleRepository $roleRepo,
        PermissionFilter $filterClass
    ) {
        $this->repo = $repo;
        $this->roleRepo = $roleRepo;
        $this->filterClass = $filterClass;
    }

    public function getDatatables()
    {
        return $this->repo->getDatatables($this->filterClass);
    }

    public function index(array $request = null)
    {
        $datas = $this->repo->with($this->indexWith)->all($request, $this->filterClass);
        $success = $datas;
        return $this->successResponse($success, __('Permission data found'));
    }

    public function store(array $request)
    {
        DB::beginTransaction();
        try {
            $permission = $this->repo->create($request);
            DB::commit();
            return $this->successResponse($permission, __('Permission created successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse(null, $e->getMessage(), $e->getCode());
        }
    }

    public function getData($id)
    {
        $permission = $this->repo->find($id);
        if ($permission) {
            return $this->successResponse($permission, __('Permission data found'));
        }
        return $this->failedResponse(null, __('Permission not found'), 404);
    }

    public function update(array $request, $id)
    {
        DB::beginTransaction();
        try {
            $permission = $this->repo->find($id);
            if ($permission) {
                $permission->update($request);
                DB::commit();
                return $this->successResponse($permission, __('Permission updated successfully'));
            }
            return $this->failedResponse(null, __('Permission not found'), 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse(null, $e->getMessage(), $e->getCode());
        }
    }

    public function addPermissionToRole(array $request)
    {
        DB::beginTransaction();
        try {
            $role = $this->roleRepo->find($request['id']);
            $permission = $this->repo->findByField('name', $request['name']);

            if (!$role || !$permission) {
                return $this->failedResponse(null, __('Either role or permission not found'), 404);
            }
            if($role->hasPermissionTo($request['name']) == false){
                $role->givePermissionTo($request['name']);
            }
             
            DB::commit();

            $result['data'] = $role;
            return $this->successResponseData($result, __('Role created successfully'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse(null, $e->getMessage(), 403);
        }
    }

    public function removePermissionFromRole($request)
    {
        DB::beginTransaction();
        try {
            $role = $this->roleRepo->find($request['id']);
            $permission = $this->repo->findByField('name', $request['name']);

            if (!$role || !$permission) {
                return $this->failedResponse(null, __('Either role or permission not found'), 404);
            }

            $role->revokePermissionTo($permission);
            DB::commit();

            $result['data'] = [ 'role' =>$role, 'permission' =>$permission];
            return $this->successResponseData($result, __('Role created successfully'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse(null, $e->getMessage(), $e->getCode());
        }
    }
}
