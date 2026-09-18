<?php

use App\Http\Controllers\QuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('quiz')->group(function () {
    Route::get('/start', [QuizController::class, 'start']);
    Route::post('/submit', [QuizController::class, 'submit']);
});
