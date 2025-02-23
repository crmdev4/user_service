<?php

use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\RateLimitThrottleMiddleware;
use App\Http\Middleware\ValidateClientHost;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        //api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
		then: function ($router) {
			Route::prefix('api/users')
				->group([
					__DIR__ . '/../routes/api.php',
				]);      
            Route::prefix('api/v1/')
				->group([
					__DIR__ . '/../routes/api_gateway.php',
				]);
    },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'throttle' => RateLimitThrottleMiddleware::class,
            'validate.host' => ValidateClientHost::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/users*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 401);
            }
        });
    })->create();
