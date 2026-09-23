<?php

namespace App\Services;

use App\Models\DailyChallenge;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizSessionService
{
    public function __construct(private GamificationService $gamification) {}

    public function start(User $user, bool $daily = false, ?string $category = null): QuizSession
    {
        if ($category !== null && ! in_array($category, $this->gamification->unlockedCategories($user), true)) {
            throw ValidationException::withMessages([
                'category' => 'This category is not unlocked for your current level.',
            ]);
        }

        $date = now()->toDateString();

        if ($daily) {
            $existing = QuizSession::where('user_id', $user->id)
                ->whereDate('daily_date', $date)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $challenge = $daily
            ? DailyChallenge::firstOrCreate(
                ['challenge_date' => $date],
                ['question_ids' => Question::inRandomOrder()->limit(10)->pluck('id')->values()->all()],
            )
            : null;

        $questionIds = $challenge?->question_ids;

        if ($questionIds === null) {
            $query = Question::query();
            if ($category !== null) {
                $query->where('category', $category);
            }
            $questionIds = $query->inRandomOrder()->limit(10)->pluck('id')->values()->all();
        }

        return QuizSession::create([
            'user_id' => $user->id,
            'question_ids' => $questionIds,
            'current_question_index' => 0,
            'lives_remaining' => 3,
            'score' => 0,
            'correct_answers' => 0,
            'status' => 'active',
            'mode' => $daily ? 'daily' : 'standard',
            'daily_date' => $daily ? $date : null,
            'question_started_at' => now(),
        ]);
    }

    /** @return array{session: QuizSession, question: Question|null, result: Result|null, timed_out: bool, correct: bool, correct_option: string} */
    public function answer(QuizSession $session, User $user, string $chosenOption): array
    {
        if ($session->user_id !== $user->id) {
            throw ValidationException::withMessages(['session_id' => 'This quiz session does not belong to you.']);
        }

        if ($session->status !== 'active') {
            throw ValidationException::withMessages(['session_id' => 'This quiz session is already finished.']);
        }

        $questionId = $session->question_ids[$session->current_question_index] ?? null;
        $question = Question::findOrFail($questionId);
        $timedOut = $session->question_started_at?->addSeconds(GamificationService::TIMER_SECONDS)->isPast() ?? false;
        $correct = ! $timedOut && $question->correct_option === $chosenOption;

        if ($correct) {
            $session->correct_answers++;
            $session->score += match ($question->difficulty) {
                'easy' => 10,
                'medium' => 15,
                'hard' => 20,
            };
        } else {
            $session->lives_remaining--;
        }

        $session->current_question_index++;
        $finished = $session->lives_remaining <= 0
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

            $xp = $this->gamification->xpForScore($session->score);
            $result = Result::create([
                'user_id' => $user->id,
                'quiz_session_id' => $session->id,
                'score' => $session->score,
                'xp_earned' => $xp,
                'is_daily' => $session->mode === 'daily',
                'correct_answers' => $session->correct_answers,
                'lives_remaining' => $session->lives_remaining,
            ]);
            $user->increment('total_score', $session->score);
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
