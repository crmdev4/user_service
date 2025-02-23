<?php

namespace App\Services\User;

use App\Filters\User\UserFilter;
use App\Services\BaseService;
use App\Repositories\User\UserRepository;

class UserService extends BaseService
{
    public function __construct(
        UserRepository $repo,
        UserFilter $filterClass,
    ) {
        $this->repo = $repo;
        $this->filterClass = $filterClass;
        $this->indexWith = [];
    }

    public function getDatatables()
    {
        dd($this->filterClass);
        
        return $this->repo->getDatatables($this->filterClass);
    }

    public function allData(array $request = null)
    {
        $datas = $this->repo->with($this->indexWith)->all($request, $this->filterClass);
        $success = $datas;
        return $this->successResponse($success, __('User data found'));
    }

    public function getData($id)
    {
        $data = $this->repo->with($this->detailWith)->find($id);
        // $success = $data;

        return response()->json([
            'message' => 'Data found',
            'data' => $data, 
        ], 200);

        // return $this->successResponse($data, __('content.message.default.success'));
    }
}
