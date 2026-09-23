<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitQuizAnswersRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Services\QuizScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Starts a quiz and scores its submitted answers.
 */
class QuizController extends Controller
{
    public function __construct(private QuizScoringService $scoringService) {}

    /**
     * Start a new quiz session by returning 10 random questions, optionally
     * scoped to a `?category=` and/or `?country=` chosen by the player.
     *
     * The response never includes `correct_option`; the client answers
     * blind and the server re-checks every answer on submit.
     */
    public function start(Request $request): AnonymousResourceCollection
    {
        $questions = Question::query()
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('country'), fn ($query) => $query->where('country', $request->string('country')))
            ->inRandomOrder()
            ->limit(10)
            ->get();

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
