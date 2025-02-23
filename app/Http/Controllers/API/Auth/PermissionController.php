<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use App\Services\Auth\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    protected $service;
    public function __construct(PermissionService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->index($request->all());
    }

    public function datatables(Request $request)
    {
        return $this->service->getDatatables($request);
    }

    public function store(PermissionRequest $request)
    {
        return $this->service->store($request->all());
    }

    public function show($id)
    {
        return $this->service->getData($id);
    }

    public function update($id, PermissionRequest $request)
    {
        return $this->service->update($request->all(), $id);
    }

    public function addPermissionToRole(Request $request)
    {
        return $this->service->addPermissionToRole($request->all());
    }

    public function removePermissionFromRole(Request $request)
    {
        return $this->service->removePermissionFromRole($request->all());
    }
}
