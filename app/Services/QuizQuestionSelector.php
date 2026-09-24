<?php

namespace App\Services;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Selects the questions for a quiz session so that difficulty ramps up
 * over the course of the quiz: easy questions first, then medium, then
 * hard, each tier itself in random order (or, for the daily challenge,
 * deterministically shuffled per user so each player gets their own,
 * but stable, set of 10 questions for the day).
 *
 * If a category/country filter leaves a tier short, the shortfall is
 * backfilled with any other matching, not-yet-selected question so the
 * quiz still reaches its full length; those backfilled questions are
 * appended last and are not guaranteed to keep the ramp strictly ordered.
 */
class QuizQuestionSelector
{
    public function __construct(private QuizSettingsService $settings) {}

    /**
     * @return Collection<int, Question>
     */
    public function select(?string $category = null, ?string $country = null): Collection
    {
        return $this->selectQuestions($category, $country, seed: null);
    }

    /**
     * Select this user's 10 questions for the given date: different from
     * every other player's, but stable across repeated calls the same day.
     *
     * @return Collection<int, Question>
     */
    public function selectForDate(Carbon $date, User $user, ?string $category = null, ?string $country = null): Collection
    {
        return $this->selectQuestions($category, $country, seed: $date->format('Y-m-d').'-'.$user->id);
    }

    /**
     * @return Collection<int, Question>
     */
    private function selectQuestions(?string $category, ?string $country, ?string $seed): Collection
    {
        $selected = new Collection;

        foreach ($this->settings->questionsPerDifficulty() as $difficulty => $quota) {
            $selected = $selected->merge($this->pick(
                $this->baseQuery($category, $country)->where('difficulty', $difficulty),
                $selected->pluck('id'),
                $quota,
                $seed,
            ));
        }

        $shortfall = $this->settings->quizLength() - $selected->count();

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

        // A deterministic stand-in for inRandomOrder(): the same seed and
        // question set always produce the same shuffle, so a repeated call
        // with the same seed (e.g. the same user re-opening today's daily
        // challenge) gets the identical, stable set of questions.
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
