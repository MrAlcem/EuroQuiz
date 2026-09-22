<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MfaController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'ok']);
});

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/mfa/verify', [AuthController::class, 'verifyMfa']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/mfa/setup', [MfaController::class, 'setup']);
    Route::post('/mfa/verify', [MfaController::class, 'verify']);
});
