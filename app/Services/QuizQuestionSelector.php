<?php

namespace App\Services;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Selects quiz questions by difficulty and optional category or country.
 * Standard quiz settings control the question quotas; daily quizzes contain
 * one question of each difficulty and are stable per user and date.
 */
class QuizQuestionSelector
{
    private const DAILY_QUIZ_LENGTH = 3;

    /**
     * @var array<string, int>
     */
    private const DAILY_QUESTIONS_PER_DIFFICULTY = [
        'easy' => 1,
        'medium' => 1,
        'hard' => 1,
    ];

    public function __construct(private QuizSettingsService $settings) {}

    /**
     * @return Collection<int, Question>
     */
    public function select(?string $category = null, ?string $country = null): Collection
    {
        return $this->selectQuestions(
            $this->settings->questionsPerDifficulty(),
            $this->settings->quizLength(),
            $category,
            $country,
            seed: null,
        );
    }

    /**
     * @return Collection<int, Question>
     */
    public function selectForDate(Carbon $date, User $user, ?string $category = null, ?string $country = null): Collection
    {
        return $this->selectQuestions(
            self::DAILY_QUESTIONS_PER_DIFFICULTY,
            self::DAILY_QUIZ_LENGTH,
            $category,
            $country,
            seed: $date->format('Y-m-d').'-'.$user->id,
        );
    }

    /**
     * @param  array<string, int>  $questionsPerDifficulty
     * @return Collection<int, Question>
     */
    private function selectQuestions(array $questionsPerDifficulty, int $quizLength, ?string $category, ?string $country, ?string $seed): Collection
    {
        $selected = new Collection;

        foreach ($questionsPerDifficulty as $difficulty => $quota) {
            $selected = $selected->merge($this->pick(
                $this->baseQuery($category, $country)->where('difficulty', $difficulty),
                $selected->pluck('id'),
                $quota,
                $seed,
            ));
        }

        $shortfall = $quizLength - $selected->count();

        if ($shortfall > 0) {
            $selected = $selected->merge($this->pick(
                $this->baseQuery($category, $country),
                $selected->pluck('id'),
                $shortfall,
                $seed,
            ));
        }

        return $selected;
    }

    /**
     * @param  Builder<Question>  $query
     * @param  BaseCollection<int, int>  $excludeIds
     * @return Collection<int, Question>
     */
    private function pick(Builder $query, BaseCollection $excludeIds, int $limit, ?string $seed): Collection
    {
        $query = $query->whereNotIn('id', $excludeIds);

        if ($seed === null) {
            return $query->inRandomOrder()->limit($limit)->get();
        }

        return $query->get()
            ->sortBy(fn (Question $question) => md5($seed.'-'.$question->id))
            ->take($limit)
            ->values();
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
