<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\EmailForgotPasswordController;
use App\Http\Controllers\API\UserVerificationController;


use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\RoleController;
use App\Http\Controllers\API\Auth\PermissionController;
use App\Http\Controllers\API\User\UserController;
use App\Http\Controllers\API\MinioServiceController;
use App\Http\Controllers\API\Auth\ValidateClientController;
use App\Http\Controllers\API\EmployeeImportController;
use App\Jobs\ProcessRabbitMQMessage;
use App\Jobs\SendWelcomeEmail;

Route::post('/validate-client', [ValidateClientController::class, 'validateClient']);
Route::get('/validate-token', function () {
    if (Auth::guard('api')->check()) {
        return response()->json([
            'success' => true,
            'message' => 'Token is valid.',
            'user' => Auth::guard('api')->user(), // Include user details if needed
        ], 200);
    } else {
        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired token.',
        ], 401); // Use a 401 status code for unauthorized access
    }
});

// test rabbitMq
Route::get('/send-message', function () {
    //ProcessRabbitMQMessage::dispatch();
    // SendWelcomeEmail::dispatch();
    // return 'Message sent to RabbitMQ!';
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot_password', [EmailForgotPasswordController::class, 'sendEmailForgotPassword']);
Route::post('/verify_token', [EmailForgotPasswordController::class, 'verifyToken']);
Route::post('/recovery_password', [EmailForgotPasswordController::class, 'recoveryPassword']);

Route::post('/verification-email-user', [UserVerificationController::class, 'verifyUser']);
Route::post('/resend_verification_email', [UserVerificationController::class, 'resendVerificationEmail']);
Route::get('/verify-email/{token}', [UserVerificationController::class, 'verify']);

Route::prefix('register')->group(callback: function () {
    Route::post('/', [AuthController::class, 'register']);
    Route::post('/driver', [AuthController::class, 'registerDriver']);
    Route::post('/activate_account', [AuthController::class, 'activate_account']);
    Route::post('/set_secondary_id', [AuthController::class, 'set_secondary_id']);
});

Route::post('/file_uploader', [MinioServiceController::class, 'fileUploader']);
Route::post('/file_uploader_new', [MinioServiceController::class, 'fileUploaderNew']);
Route::post('/file_blob_uploader', [MinioServiceController::class, 'handleBlobUpload']);
Route::post('/file_delete', [MinioServiceController::class, 'fileDelete']);

// get user by driver_id
Route::prefix('user')->group(function () {
    Route::get('/get/{id}', [UserController::class, 'checkAccountById']);
    Route::get('/deactivate-user/{id}', [UserController::class, 'deactivateAccountById']);
    Route::get('/reactivate-user/{id}', [UserController::class, 'reactivateAccountById']);
    Route::put('/update/{id}', [UserController::class, 'updateAccountById']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/', function () {
        return Auth::guard('api')->check();;
    });

    Route::post('logout', [AuthController::class, 'logout']);
    Route::prefix('user')->group(function () {
        Route::get('/', [AuthController::class, 'user']);
       // Route::get('/', [UserController::class, 'users']);
        Route::post('/', [AuthController::class, 'update']);

        Route::post('/create', [UserController::class, 'create']);
        Route::get('/list', [UserController::class, 'users']);
        Route::delete('/{id}', [UserController::class, 'delete']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::patch('/{id}', [UserController::class, 'update']);
        Route::patch('/banned/{id}', [UserController::class, 'banned']);
        Route::get('/banned/user_info/{id}', [UserController::class, 'getUserBannedInfo']);
    });

    Route::post('/employees/import', [EmployeeImportController::class, 'importEmployees']);
    Route::get('/employees/import/progress/{importKey}', [EmployeeImportController::class, 'getImportProgress']);

    Route::post('/change_password', [AuthController::class, 'change_password']);
    Route::post('/reset_password', [AuthController::class, 'resetPassword']);

    Route::prefix('roles')->group(function () {
        Route::controller(RoleController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/datatables', 'datatables');
            Route::post('/store', 'store');
            Route::get('/detail/{id}', 'show');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'delete');
            Route::post('/assign', 'assignUserToRole');
            Route::post('/remove', 'removeUserFromRole');
        });
    });

    Route::prefix('permission')->group(function () {
        Route::controller(PermissionController::class)->group(function () {
            Route::get('/', 'index')->middleware('permission:view_permission');
            Route::get('/datatables', 'datatables')->middleware('permission:view_permission');
            Route::post('/store', 'store')->middleware('permission:create_permission');
            Route::get('/detail/{id}', 'show')->middleware('permission:view_permission');
            Route::post('/update/{id}', 'update')->middleware('permission:update_permission');
            Route::delete('/delete/{id}', 'delete')->middleware('permission:delete_permission');

            Route::post('/add', 'addPermissionToRole');
            Route::post('/remove', 'removePermissionFromRole');
        });
    });
});
