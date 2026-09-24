<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\DashboardStatsController;
use App\Http\Controllers\Api\Admin\QuestionController;
use App\Http\Controllers\Api\Admin\ResultController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\MfaController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ResultController;
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
    Route::get('/results/{result}', [ResultController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'locale'])->prefix('quiz')->group(function () {
    Route::get('/options', [QuizController::class, 'options']);
    Route::get('/start', [QuizController::class, 'start']);
    Route::get('/daily', [QuizController::class, 'startDaily']);
    Route::get('/sessions/{quizSession}/questions', [QuizController::class, 'questions']);
    Route::post('/sessions/{quizSession}/answer', [QuizController::class, 'answer']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/questions/options', [QuestionController::class, 'options']);
    Route::post('/questions/import', [QuestionController::class, 'import']);
    Route::get('/questions/export', [QuestionController::class, 'export']);
    Route::apiResource('questions', QuestionController::class);
    Route::get('/dashboard/stats', DashboardStatsController::class);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/settings', [SettingController::class, 'show']);
    Route::put('/settings', [SettingController::class, 'update']);
    Route::apiResource('users', UserController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::apiResource('results', ResultController::class)->only(['index', 'show']);
});
