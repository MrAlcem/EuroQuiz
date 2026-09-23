<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerQuizQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Models\QuizSession;
use App\Services\GamificationService;
use App\Services\QuizSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Runs a stateful quiz session: `start`/`startDaily` pick the 10 questions
 * and open a session, `answer` scores one question at a time against a
 * server-tracked per-question timer.
 */
class QuizController extends Controller
{
    public function __construct(
        private QuizSessionService $sessionService,
        private GamificationService $gamification,
    ) {}

    /**
     * Start a new quiz session, optionally scoped to a `?category=`
     * (must be unlocked for the player's level) and/or `?country=`.
     */
    public function start(Request $request): JsonResponse
    {
        $session = $this->sessionService->start(
            $request->user(),
            daily: false,
            category: $request->filled('category') ? $request->string('category')->toString() : null,
            country: $request->filled('country') ? $request->string('country')->toString() : null,
        );

        return $this->sessionResponse($session, $request);
    }

    /**
     * Start (or resume) today's daily challenge session for the player.
     */
    public function startDaily(Request $request): JsonResponse
    {
        $session = $this->sessionService->start($request->user(), daily: true);

        return $this->sessionResponse($session, $request, daily: true);
    }

    /**
     * Score one answer within an active session and return the next
     * question, or the final result once the session ends.
     */
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

    private function sessionResponse(QuizSession $session, Request $request, bool $daily = false): JsonResponse
    {
        $questions = Question::whereIn('id', $session->question_ids)
            ->get()
            ->sortBy(fn (Question $question) => array_search($question->id, $session->question_ids))
            ->values();

        $payload = [
            'data' => QuestionResource::collection($questions),
            'session_id' => $session->id,
            'timer_seconds' => GamificationService::TIMER_SECONDS,
            'gamification' => $this->gamification->summary($request->user()),
        ];

        if ($daily) {
            $alreadyCompleted = $session->status === 'completed';
            $result = $alreadyCompleted ? $session->result : null;

            $payload['daily'] = true;
            $payload['already_completed'] = $alreadyCompleted;
            $payload['completed_result'] = $result ? [
                'score' => $result->score,
                'correct_answers' => $result->correct_answers,
                'lives_remaining' => $result->lives_remaining,
                'completed_at' => $result->created_at->toIso8601String(),
            ] : null;
            $payload['resets_at'] = $this->sessionService->dailyResetsAt()->toIso8601String();
        }

        return response()->json($payload);
    }
}
