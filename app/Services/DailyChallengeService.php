<?php

namespace App\Services;

use App\Models\DailyChallengeAttempt;
use App\Models\Question;
use App\Models\Result;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Runs the once-a-day bonus quiz: each player gets their own set of 10
 * questions for the date (stable if they reopen it, but different from
 * other players'), and completing it for the first time that day earns
 * a flat bonus on top of the normal scoring.
 */
class DailyChallengeService
{
    private const BONUS = 20;

    public function __construct(
        private QuizQuestionSelector $questionSelector,
        private QuizScoringService $scoringService,
    ) {}

    /**
     * @return Collection<int, Question>
     */
    public function questionsForToday(User $user): Collection
    {
        return $this->questionSelector->selectForDate(Carbon::today(), $user);
    }

    public function hasCompletedToday(User $user): bool
    {
        return $this->todaysAttempt($user) !== null;
    }

    /**
     * The user's daily challenge attempt for today, if they've already
     * completed it, with its scored result loaded.
     */
    public function todaysAttempt(User $user): ?DailyChallengeAttempt
    {
        return DailyChallengeAttempt::query()
            ->where('user_id', $user->id)
            ->whereDate('challenge_date', Carbon::today())
            ->with('result')
            ->first();
    }

    /**
     * The moment the daily challenge next resets, i.e. the start of
     * tomorrow, so the client can show a countdown.
     */
    public function resetsAt(): Carbon
    {
        return Carbon::tomorrow()->startOfDay();
    }

    /**
     * Score today's daily challenge and record the attempt, or return
     * `null` if the user already completed it today.
     *
     * @param  array<int, array{question_id: int, chosen_option: ?string}>  $answers
     */
    public function submit(User $user, array $answers): ?Result
    {
        if ($this->hasCompletedToday($user)) {
            return null;
        }

        return DB::transaction(function () use ($user, $answers) {
            $result = $this->scoringService->score($user, $answers, bonus: self::BONUS);

            DailyChallengeAttempt::create([
                'user_id' => $user->id,
                'challenge_date' => Carbon::today(),
                'result_id' => $result->id,
            ]);

            return $result;
        });
    }
}
