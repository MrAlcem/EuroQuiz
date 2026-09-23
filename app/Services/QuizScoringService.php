<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Scores a submitted batch of quiz answers and persists the result.
 *
 * The quiz is stateless: the client is trusted only to send back the
 * {question_id, chosen_option} pairs in the order the questions were
 * presented. Every correct answer is re-read from the database here;
 * the client never supplies correctness itself. Lives and the
 * early-stop rule are applied while iterating that order, so trailing
 * questions submitted after the third wrong answer do not count.
 *
 * A `null` `chosen_option` means the client's per-question timer ran
 * out before an option was picked; it never matches `correct_option`
 * and so is scored exactly like any other wrong answer.
 *
 * A run of `STREAK_LENGTH` consecutive correct answers earns a flat
 * `STREAK_BONUS` on top of the per-question points, and again for every
 * further `STREAK_LENGTH` correct answers the streak keeps going. A
 * wrong (or timed-out) answer resets the streak to zero.
 */
class QuizScoringService
{
    private const STARTING_LIVES = 3;

    private const STREAK_LENGTH = 3;

    private const STREAK_BONUS = 5;

    /**
     * @var array<string, int>
     */
    private const POINTS_BY_DIFFICULTY = [
        'easy' => 15,
        'medium' => 25,
        'hard' => 40,
    ];

    /**
     * Score the given answers for the user and persist a `Result`.
     *
     * @param  array<int, array{question_id: int, chosen_option: ?string}>  $answers
     */
    public function score(User $user, array $answers): Result
    {
        $questions = Question::whereIn('id', array_column($answers, 'question_id'))
            ->get()
            ->keyBy('id');

        $lives = self::STARTING_LIVES;
        $score = 0;
        $correctAnswers = 0;
        $streak = 0;

        foreach ($answers as $answer) {
            if ($lives <= 0) {
                break;
            }

            $question = $questions->get($answer['question_id']);

            if ($question->correct_option === $answer['chosen_option']) {
                $score += self::POINTS_BY_DIFFICULTY[$question->difficulty];
                $correctAnswers++;
                $streak++;

                if ($streak % self::STREAK_LENGTH === 0) {
                    $score += self::STREAK_BONUS;
                }
            } else {
                $lives--;
                $streak = 0;
            }
        }

        return DB::transaction(function () use ($user, $score, $correctAnswers, $lives) {
            $result = Result::create([
                'user_id' => $user->id,
                'score' => $score,
                'correct_answers' => $correctAnswers,
                'lives_remaining' => $lives,
            ]);

            $user->increment('total_score', $score);

            return $result;
        });
    }
}
