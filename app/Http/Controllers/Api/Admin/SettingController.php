<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\QuizSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function show(QuizSettingsService $settings): JsonResponse
    {
        return response()->json(['data' => $settings->all()]);
    }

    public function update(Request $request, QuizSettingsService $settings): JsonResponse
    {
        $validated = $request->validate([
            'quiz_length' => ['required', 'integer', 'min:1', 'max:50'],
            'lives' => ['required', 'integer', 'min:1', 'max:10'],
            'timer_seconds' => ['required', 'integer', 'min:5', 'max:300'],
            'easy_questions' => ['required', 'integer', 'min:0', 'max:50'],
            'medium_questions' => ['required', 'integer', 'min:0', 'max:50'],
            'hard_questions' => ['required', 'integer', 'min:0', 'max:50'],
            'easy_points' => ['required', 'integer', 'min:0', 'max:1000'],
            'medium_points' => ['required', 'integer', 'min:0', 'max:1000'],
            'hard_points' => ['required', 'integer', 'min:0', 'max:1000'],
            'streak_length' => ['required', 'integer', 'min:1', 'max:20'],
            'streak_bonus' => ['required', 'integer', 'min:0', 'max:1000'],
            'daily_bonus' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $difficultyTotal = $validated['easy_questions']
            + $validated['medium_questions']
            + $validated['hard_questions'];

        if ($difficultyTotal !== $validated['quiz_length']) {
            return response()->json([
                'message' => 'Easy, medium and hard question counts must add up to the quiz length.',
            ], 422);
        }

        DB::transaction(function () use ($validated): void {
            foreach ($validated as $key => $value) {
                DB::table('quiz_settings')->where('key', $key)->update([
                    'value' => $value,
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json(['data' => $settings->all()]);
    }
}
