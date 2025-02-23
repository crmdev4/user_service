<?php

namespace App\Http\Controllers\API_GATEWAY;

use App\Http\Controllers\Controller;
use App\Services\RelayRequestService;
use Exception;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RelayRequestController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function __invoke(Request $request, RelayRequestService $relayRequestService)
    {
        
        return $relayRequestService->relay($request);
    }
}