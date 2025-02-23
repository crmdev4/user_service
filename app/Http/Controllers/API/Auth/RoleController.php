<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Services\Auth\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $service;
    public function __construct(RoleService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->index($request->all());
    }

    public function datatables(Request $request)
    {
        return $this->service->getDatatables($request->all());
    }

    public function store(RoleRequest $request)
    {
        return $this->service->store($request->all());
    }

    public function show($id)
    {
        return $this->service->getData($id);
    }

    public function delete($id)
    {
        return $this->service->delete($id);
    }

    public function update($id, RoleRequest $request)
    {
        return $this->service->update($request->all(), $id);
    }

    public function assignUserToRole(Request $request)
    {
        return $this->service->assignUserToRole($request->all());
    }

    public function removeUserFromRole(Request $request)
    {
        return $this->service->removeUserFromRole($request->all());
    }
}
