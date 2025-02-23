<?php

namespace App\Repositories\Auth;

use App\Repositories\BaseRepository;
use App\Models\Permission;

class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $model)
    {
        $this->model = $model;
    }
}
