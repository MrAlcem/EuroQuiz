<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitQuizAnswersRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Services\QuizScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Starts a quiz and scores its submitted answers.
 */
class QuizController extends Controller
{
    public function __construct(private QuizScoringService $scoringService) {}

    /**
     * Start a new quiz session by returning 10 random questions.
     *
     * The response never includes `correct_option`; the client answers
     * blind and the server re-checks every answer on submit.
     */
    public function start(): AnonymousResourceCollection
    {
        $questions = Question::inRandomOrder()->limit(10)->get();

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
