<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitQuizAnswersRequest;
use App\Http\Requests\AnswerQuizQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Services\QuizQuestionSelector;
use App\Models\Question;
use App\Models\QuizSession;
use App\Services\GamificationService;
use App\Services\QuizScoringService;
use App\Services\QuizSessionService;
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
        private QuizSessionService $sessionService,
        private GamificationService $gamification,
    ) {}

    /**
     * Start a new quiz session by returning 10 questions ordered from easy
     * to hard, optionally scoped to a `?category=` and/or `?country=`
     * chosen by the player.
     *
     * The response never includes `correct_option`; the client answers
     * blind and the server re-checks every answer on submit.
     */
    public function start(Request $request): JsonResponse
    {
        $session = $this->sessionService->start($request->user(), false, $request->query('category'));
        $questions = Question::whereIn('id', $session->question_ids)
            ->get()
            ->sortBy(fn (Question $question) => array_search($question->id, $session->question_ids))
            ->values();
    public function start(Request $request): AnonymousResourceCollection
    {
        $questions = $this->questionSelector->select(
            $request->filled('category') ? $request->string('category')->toString() : null,
            $request->filled('country') ? $request->string('country')->toString() : null,
        );

        return response()->json([
            'data' => QuestionResource::collection($questions),
            'session_id' => $session->id,
            'timer_seconds' => GamificationService::TIMER_SECONDS,
            'gamification' => $this->gamification->summary($request->user()),
        ]);
    }

    public function startDaily(Request $request): JsonResponse
    {
        $session = $this->sessionService->start($request->user(), true);
        $questions = Question::whereIn('id', $session->question_ids)
            ->get()
            ->sortBy(fn (Question $question) => array_search($question->id, $session->question_ids))
            ->values();

        return response()->json([
            'data' => QuestionResource::collection($questions),
            'session_id' => $session->id,
            'timer_seconds' => GamificationService::TIMER_SECONDS,
            'daily' => true,
            'gamification' => $this->gamification->summary($request->user()),
        ]);
    }

    public function answer(AnswerQuizQuestionRequest $request, QuizSession $quizSession): JsonResponse
    {
        $outcome = $this->sessionService->answer(
            $quizSession,
            $request->user(),
            $request->validated('chosen_option'),
        );

        return response()->json([
            'correct' => $outcome['correct'],
            'correct_option' => $outcome['correct_option'],
            'timed_out' => $outcome['timed_out'],
            'lives_remaining' => $outcome['session']->lives_remaining,
            'next_question' => $outcome['question']
                ? (new QuestionResource($outcome['question']))->resolve()
                : null,
            'finished' => $outcome['result'] !== null,
            'result' => $outcome['result'] ? [
                'score' => $outcome['result']->score,
                'correct_answers' => $outcome['result']->correct_answers,
                'lives_remaining' => $outcome['result']->lives_remaining,
                'xp_earned' => $outcome['result']->xp_earned,
                'daily' => $outcome['result']->is_daily,
            ] : null,
        ]);
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
