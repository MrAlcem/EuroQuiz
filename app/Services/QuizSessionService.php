<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Runs a stateful quiz session one question at a time: `start()` picks the
 * questions (ramped from easy to hard via `QuizQuestionSelector` — 10 for a
 * standard quiz, 3 for the daily challenge, which is the same static set
 * for every player on a given date), `answer()` scores each submitted
 * answer against the server-tracked `question_started_at` timer and
 * persists a `Result` once the session ends. The daily challenge has no
 * lives: a wrong answer never ends it early.
 */
class QuizSessionService
{
    public function __construct(
        private GamificationService $gamification,
        private QuizQuestionSelector $questionSelector,
        private QuizSettingsService $settings,
    ) {}

    public function start(User $user, bool $daily = false, ?string $category = null, ?string $country = null): QuizSession
    {
        if ($category !== null && ! in_array($category, $this->gamification->unlockedCategories($user), true)) {
            throw ValidationException::withMessages([
                'category' => 'This category is not unlocked for your current level.',
            ]);
        }

        $date = Carbon::today();

        if ($daily) {
            $existing = QuizSession::where('user_id', $user->id)
                ->whereDate('daily_date', $date)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $questionIds = $daily
            ? $this->questionSelector->selectForDate($date, $user)->pluck('id')->values()->all()
            : $this->questionSelector->select($category, $country)->pluck('id')->values()->all();

        return QuizSession::create([
            'user_id' => $user->id,
            'question_ids' => $questionIds,
            'current_question_index' => 0,
            'lives_remaining' => $this->settings->lives(),
            'score' => 0,
            'correct_answers' => 0,
            'current_streak' => 0,
            'status' => 'active',
            'mode' => $daily ? 'daily' : 'standard',
            'daily_date' => $daily ? $date : null,
            'question_started_at' => now(),
        ]);
    }

    /**
     * The user's own daily session for today, if one exists (whether
     * still active or already completed).
     */
    public function todaysDailySession(User $user): ?QuizSession
    {
        return QuizSession::where('user_id', $user->id)
            ->whereDate('daily_date', Carbon::today())
            ->first();
    }

    /**
     * The moment the daily challenge next resets, i.e. the start of
     * tomorrow, so the client can show a countdown.
     */
    public function dailyResetsAt(): Carbon
    {
        return Carbon::tomorrow()->startOfDay();
    }

    /** @return array{session: QuizSession, question: Question|null, result: Result|null, timed_out: bool, correct: bool, correct_option: string} */
    public function answer(QuizSession $session, User $user, ?string $chosenOption): array
    {
        if ($session->user_id !== $user->id) {
            throw ValidationException::withMessages(['session_id' => 'This quiz session does not belong to you.']);
        }

        if ($session->status !== 'active') {
            throw ValidationException::withMessages(['session_id' => 'This quiz session is already finished.']);
        }

        $isDaily = $session->mode === 'daily';

        $questionId = $session->question_ids[$session->current_question_index] ?? null;
        $question = Question::findOrFail($questionId);
        $timedOut = $session->question_started_at?->addSeconds($question->time_limit_seconds)->isPast() ?? false;
        $correct = ! $timedOut && $chosenOption !== null && $question->correct_option === $chosenOption;

        if ($correct) {
            $session->correct_answers++;
            $session->current_streak++;
            $session->score += $this->settings->pointsByDifficulty()[$question->difficulty];

            if ($session->current_streak % $this->settings->get('streak_length') === 0) {
                $session->score += $this->settings->get('streak_bonus');
            }
        } else {
            // The daily challenge has no lives: a wrong answer just moves on.
            if (! $isDaily) {
                $session->lives_remaining--;
            }
            $session->current_streak = 0;
        }

        $session->current_question_index++;
        $finished = (! $isDaily && $session->lives_remaining <= 0)
            || $session->current_question_index >= count($session->question_ids);
        $session->status = $finished ? 'completed' : 'active';
        $session->question_started_at = $finished ? null : now();

        return DB::transaction(function () use ($session, $user, $question, $correct, $finished, $timedOut) {
            $session->save();

            if (! $finished) {
                return [
                    'session' => $session,
                    'question' => Question::find($session->question_ids[$session->current_question_index]),
                    'result' => null,
                    'timed_out' => $timedOut,
                    'correct' => $correct,
                    'correct_option' => $question->correct_option,
                ];
            }

            $score = $session->score + ($session->mode === 'daily' ? $this->settings->get('daily_bonus') : 0);
            $xp = $this->gamification->xpForScore($score);
            $result = Result::create([
                'user_id' => $user->id,
                'quiz_session_id' => $session->id,
                'score' => $score,
                'xp_earned' => $xp,
                'is_daily' => $session->mode === 'daily',
                'correct_answers' => $session->correct_answers,
                'lives_remaining' => $session->lives_remaining,
            ]);
            $user->increment('total_score', $score);
            $user->increment('xp', $xp);

            return [
                'session' => $session,
                'question' => null,
                'result' => $result,
                'timed_out' => $timedOut,
                'correct' => $correct,
                'correct_option' => $question->correct_option,
            ];
        });
    }
}
