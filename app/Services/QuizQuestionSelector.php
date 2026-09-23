<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Selects the questions for a quiz session so that difficulty ramps up
 * over the course of the quiz: easy questions first, then medium, then
 * hard, each tier itself in random order.
 *
 * If a category/country filter leaves a tier short, the shortfall is
 * backfilled with any other matching, not-yet-selected question so the
 * quiz still reaches its full length; those backfilled questions are
 * appended last and are not guaranteed to keep the ramp strictly ordered.
 */
class QuizQuestionSelector
{
    private const QUIZ_LENGTH = 10;

    /**
     * @var array<string, int>
     */
    private const QUESTIONS_PER_DIFFICULTY = [
        'easy' => 4,
        'medium' => 3,
        'hard' => 3,
    ];

    /**
     * @return Collection<int, Question>
     */
    public function select(?string $category, ?string $country): Collection
    {
        $selected = new Collection;

        foreach (self::QUESTIONS_PER_DIFFICULTY as $difficulty => $quota) {
            $selected = $selected->merge(
                $this->baseQuery($category, $country)
                    ->where('difficulty', $difficulty)
                    ->whereNotIn('id', $selected->pluck('id'))
                    ->inRandomOrder()
                    ->limit($quota)
                    ->get()
            );
        }

        $shortfall = self::QUIZ_LENGTH - $selected->count();

        if ($shortfall > 0) {
            $selected = $selected->merge(
                $this->baseQuery($category, $country)
                    ->whereNotIn('id', $selected->pluck('id'))
                    ->inRandomOrder()
                    ->limit($shortfall)
                    ->get()
            );
        }

        return $selected;
    }

    /**
     * @return Builder<Question>
     */
    private function baseQuery(?string $category, ?string $country): Builder
    {
        return Question::query()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($country, fn ($query) => $query->where('country', $country));
    }
}
