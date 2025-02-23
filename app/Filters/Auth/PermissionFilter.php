<?php

namespace App\Filters\Auth;

use App\Filters\BaseFilter;
use App\Models\Permission;

class PermissionFilter extends BaseFilter
{
    public function __construct(Permission $model)
    {
        $this->model = $model;
        $this->fields = [
            'id',
            'name',
        ];

        $this->filterFields = [
            'name',
        ];

        $this->customSearch = [
            'name',
        ];
    }

    public function filterQ($builder, $value)
    {
        $builder = $this->qFilterFormatter($builder, $value, $this->fields);
        return $builder;
    }

    // Digunakan untuk filter data datatable
    public function dtFilterName($builder, $search)
    {
        info($search);
        return $builder->where('name', 'LIKE', '%' . $search . '%');
    }

    // Digunakan untuk override search datatable
    public function dtSearchName($builder, $search)
    {
        return $builder->where('name', 'LIKE', '%' . $search . '%');
    }
}
