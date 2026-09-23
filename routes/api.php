<?php

use App\Http\Controllers\Api\Admin\QuestionController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\MfaController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\QuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'ok']);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/mfa/verify', [AuthController::class, 'verifyMfa']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/mfa/setup', [MfaController::class, 'setup']);
    Route::post('/mfa/verify', [MfaController::class, 'verify']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
});

Route::middleware('auth:sanctum')->prefix('quiz')->group(function () {
    Route::get('/start', [QuizController::class, 'start']);
    Route::post('/submit', [QuizController::class, 'submit']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('questions', QuestionController::class);
    Route::get('/users', [UserController::class, 'index']);
});
