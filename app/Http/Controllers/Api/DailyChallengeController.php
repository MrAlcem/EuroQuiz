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
 * Serves the once-a-day bonus quiz: the same 10 questions for every
 * player on a given date, scored with an extra completion bonus the
 * first time each player submits it that day.
 */
class DailyChallengeController extends Controller
{
    public function __construct(private DailyChallengeService $dailyChallengeService) {}

    /**
     * Show today's daily challenge questions and whether the user has
     * already completed it today.
     */
    public function show(Request $request): AnonymousResourceCollection
    {
        return QuestionResource::collection($this->dailyChallengeService->questionsForToday())
            ->additional([
                'already_completed' => $this->dailyChallengeService->hasCompletedToday($request->user()),
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
            ], 409);
        }

        return response()->json([
            'score' => $result->score,
            'correct_answers' => $result->correct_answers,
            'lives_remaining' => $result->lives_remaining,
        ]);
    }
}
