<?php

namespace App\Repositories;

use App\Models\EndpointRegister;
use App\Repositories\BaseRepository;

class EndpointRegisterRepository extends BaseRepository
{
    public function __construct(EndpointRegister $model)
    {
        $this->model = $model;
    }
}
