<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitQuizAnswersRequest;
use App\Http\Resources\QuestionResource;
use App\Services\DailyChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Serves the once-a-day bonus quiz: each player gets their own 10
 * questions for the date, scored with an extra completion bonus the
 * first time they submit it that day.
 */
class DailyChallengeController extends Controller
{
    public function __construct(private DailyChallengeService $dailyChallengeService) {}

    /**
     * Show today's daily challenge questions, whether the user has
     * already completed it today (and if so, with what result), and
     * when the challenge next resets.
     */
    public function show(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $attempt = $this->dailyChallengeService->todaysAttempt($user);

        return QuestionResource::collection($this->dailyChallengeService->questionsForToday($user))
            ->additional([
                'already_completed' => $attempt !== null,
                'completed_result' => $attempt ? [
                    'score' => $attempt->result->score,
                    'correct_answers' => $attempt->result->correct_answers,
                    'lives_remaining' => $attempt->result->lives_remaining,
                    'completed_at' => $attempt->created_at->toIso8601String(),
                ] : null,
                'resets_at' => $this->dailyChallengeService->resetsAt()->toIso8601String(),
            ]);
    }

    /**
     * Score a submission of today's daily challenge, if not already
     * completed today.
     */
    public function submit(SubmitQuizAnswersRequest $request): JsonResponse
    {
        $result = $this->dailyChallengeService->submit($request->user(), $request->validated('answers'));

        if ($result === null) {
            return response()->json([
                'message' => "You've already completed today's daily challenge.",
                'resets_at' => $this->dailyChallengeService->resetsAt()->toIso8601String(),
            ], 409);
        }

        return response()->json([
            'score' => $result->score,
            'correct_answers' => $result->correct_answers,
            'lives_remaining' => $result->lives_remaining,
        ]);
    }
}
