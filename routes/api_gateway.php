<?php

use App\Http\Controllers\API_GATEWAY\RelayRequestController;
use App\Http\Controllers\API\EndpointRegisterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/status', function () {
    return response()->json(['status' => 'API Gateway is up']);
});

Route::middleware('validate.host')->group(function () {
    Route::get('/needvalidatemyhost', function (Request $request) {

        $host = $request->attributes->get('host');
        $isAllowed = $request->attributes->get('is_allowed');
        $isActivated = $request->attributes->get('is_activated');

        return response()->json([
            'message' => 'host validated successfully.',
            'host' => $host,
            'is_allowed' => $isAllowed,
            'is_activated' => $isActivated,
        ]);
    });
});

Route::prefix('/relay')->group(function () {
    Route::get('/', [EndpointRegisterController::class, 'index']);
    Route::post('/', [EndpointRegisterController::class, 'create']);
    Route::patch('/', [EndpointRegisterController::class, 'update']);
    Route::delete('/', [EndpointRegisterController::class, 'delete']);

    Route::prefix('/relation')->group(function () {
        Route::post('/', [EndpointRegisterController::class, 'createRelation']);
        Route::get('/all', [EndpointRegisterController::class, 'list']);
        Route::post('/show/{id}', [EndpointRegisterController::class, 'getRelationById']);
        Route::delete('/{rel_id}/delete/{id}', [EndpointRegisterController::class, 'deleteRelationById']);
    });
});

Route::middleware(['auth:api', 'validate.host'])->group(function () {
    Route::any('/{path?}', [RelayRequestController::class, '__invoke'])
        ->where('path', '.*')
        ->name('relay');
});
