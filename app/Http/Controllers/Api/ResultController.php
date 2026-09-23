<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResultAnswerResource;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exposes a single finished quiz result, including the per-question
 * answer log, so a player can review what they answered.
 */
class ResultController extends Controller
{
    /**
     * Show a result owned by the authenticated user, with its answers.
     */
    public function show(Request $request, Result $result): JsonResponse
    {
        abort_if($result->user_id !== $request->user()->id, 403);

        $result->load('answers.question');

        return response()->json([
            'id' => $result->id,
            'score' => $result->score,
            'correct_answers' => $result->correct_answers,
            'lives_remaining' => $result->lives_remaining,
            'xp_earned' => $result->xp_earned,
            'daily' => $result->is_daily,
            'date' => $result->created_at->toDateString(),
            'answers' => ResultAnswerResource::collection($result->answers),
        ]);
    }
}
