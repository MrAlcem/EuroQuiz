<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitQuizAnswersRequest;
use App\Http\Resources\QuestionResource;
use App\Services\QuizQuestionSelector;
use App\Services\QuizScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Starts a quiz and scores its submitted answers.
 */
class QuizController extends Controller
{
    public function __construct(
        private QuizQuestionSelector $questionSelector,
        private QuizScoringService $scoringService,
    ) {}

    /**
     * Start a new quiz session by returning 10 questions ordered from easy
     * to hard, optionally scoped to a `?category=` and/or `?country=`
     * chosen by the player.
     *
     * The response never includes `correct_option`; the client answers
     * blind and the server re-checks every answer on submit.
     */
    public function start(Request $request): AnonymousResourceCollection
    {
        $questions = $this->questionSelector->select(
            $request->filled('category') ? $request->string('category')->toString() : null,
            $request->filled('country') ? $request->string('country')->toString() : null,
        );

        return QuestionResource::collection($questions);
    }

    /**
     * Score a submitted batch of answers and persist the result.
     */
    public function submit(SubmitQuizAnswersRequest $request): JsonResponse
    {
        $result = $this->scoringService->score($request->user(), $request->validated('answers'));

        return response()->json([
            'score' => $result->score,
            'correct_answers' => $result->correct_answers,
            'lives_remaining' => $result->lives_remaining,
        ]);
    }
}
