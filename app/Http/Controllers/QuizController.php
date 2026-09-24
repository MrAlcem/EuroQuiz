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
 * Runs a stateful quiz session: `start` picks 10 questions and opens a
 * session, `startDaily` opens the day's 3-question, lives-free daily
 * challenge (the same static set of questions for every player), and
 * `answer` scores one question at a time against a server-tracked
 * per-question timer.
 */
class QuizController extends Controller
{
    public function __construct(
        private QuizSessionService $sessionService,
        private GamificationService $gamification,
    ) {}

    /**
     * Return the filter choices available in questions for this player.
     */
    public function options(Request $request): JsonResponse
    {
        $unlockedCategories = $this->gamification->unlockedCategories($request->user());

        return response()->json([
            'data' => [
                'categories' => Question::query()
                    ->whereIn('category', $unlockedCategories)
                    ->whereNotNull('category')
                    ->where('category', '<>', '')
                    ->distinct()
                    ->orderBy('category')
                    ->pluck('category')
                    ->values(),
                'countries' => Question::query()
                    ->whereNotNull('country')
                    ->where('country', '<>', '')
                    ->distinct()
                    ->orderBy('country')
                    ->pluck('country')
                    ->values(),
            ],
        ]);
    }

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
     * The full question set for an already-started session, re-translated
     * into `?lang=`. Read-only — unlike `start`/`startDaily`, it never
     * touches `question_started_at`, so switching language mid-quiz doesn't
     * cost the player any time on the per-question timer.
     */
    public function questions(Request $request, QuizSession $quizSession): JsonResponse
    {
        abort_if($quizSession->user_id !== $request->user()->id, 403);

        return response()->json([
            'data' => $this->translatedQuestions($quizSession),
        ]);
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
                'id' => $outcome['result']->id,
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
        $payload = [
            'data' => $this->translatedQuestions($session),
            'session_id' => $session->id,
            // The limit for the question the player is about to see; each
            // question in `data` also carries its own `time_limit_seconds`,
            // since the server enforces it per question, not with one
            // fixed timer for the whole session.
            'timer_seconds' => $questions->first()?->time_limit_seconds ?? GamificationService::TIMER_SECONDS,
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

    /** @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection */
    private function translatedQuestions(QuizSession $session)
    {
        $questions = Question::whereIn('id', $session->question_ids)
            ->get()
            ->sortBy(fn (Question $question) => array_search($question->id, $session->question_ids))
            ->values();

        return QuestionResource::collection($questions);
    }
}
