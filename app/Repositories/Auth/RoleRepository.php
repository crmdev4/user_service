<?php

namespace App\Repositories\Auth;

use App\Models\Role;
use App\Repositories\BaseRepository;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $model)
    {
        $this->model = $model;
    }
}
