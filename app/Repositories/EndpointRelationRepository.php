<?php

namespace App\Repositories;

use App\Models\EndpointRelation;
use App\Repositories\BaseRepository;

class EndpointRelationRepository extends BaseRepository
{
    public function __construct(EndpointRelation $model)
    {
        $this->model = $model;
    }
}
