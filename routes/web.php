<?php

use App\Http\Controllers\API\EmailForgotPasswordController;
use App\Http\Controllers\API\UserVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\RelayController;
use App\Http\Controllers\API\EmployeeVerificationController;

Route::get('/', function () {
    return response()->json(['message' => 'OKE'], 200);
});

Route::prefix('/dashboard/relay')->group(function () {
    Route::get('/list', [RelayController::class, 'index']);
    Route::get('/relation/{id}', [RelayController::class, 'getRelation']);
});

// Route::get('/verify-email', [EmployeeVerificationController::class, 'verify'])->name('verify.email');
Route::get('/verify-email/{token}', [UserVerificationController::class, 'verify']);
// Route::get('/verify-email/{token}', [EmployeeVerificationController::class, 'verifyToken'])->name('verify.email.token');

Route::get('/forgot-password/{token}', [EmailForgotPasswordController::class, 'verify'])->name('verify.email-forgot-password');
Route::get('/verify-user-email', [UserVerificationController::class, 'verifyUser'])->name('verify.user.email');
