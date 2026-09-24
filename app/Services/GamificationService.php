<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\UserLevel;

class GamificationService
{
    public function __construct(private QuizSettingsService $settings) {}

    public function timerSeconds(): int
    {
        return $this->settings->timerSeconds();
    }

    /**
     * Delegates to `UserLevel`, the single source of truth for a user's
     * level (Beginner/Pro/Expert, based on total_score) shared with the
     * profile, leaderboard and admin endpoints.
     */
    public function level(User $user): string
    {
        return UserLevel::fromScore($user->total_score)->name;
    }

    /** @return array<int, string> */
    public function unlockedCategories(User $user): array
    {
        $levelCategories = match ($this->level($user)) {
            'Expert' => ['Geography', 'Nature', 'History', 'Culture'],
            'Pro' => ['Geography', 'Nature', 'History'],
            default => ['Geography', 'Nature'],
        };

        $customCategories = Category::query()
            ->whereNotIn('name', ['Geography', 'Nature', 'History', 'Culture'])
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return array_values(array_unique([...$levelCategories, ...$customCategories]));
    }

    /**
     * The level a user needs to reach to unlock this category, or null if
     * it's already unlocked at every level (the two base categories and
     * any custom category).
     */
    public function requiredLevelForCategory(string $category): ?UserLevel
    {
        return match ($category) {
            'History' => UserLevel::Pro,
            'Culture' => UserLevel::Expert,
            default => null,
        };
    }

    public function xpForScore(int $score): int
    {
        return $score;
    }

    /** @return array{level: string, xp: int, unlocked_categories: array<int, string>} */
    public function summary(User $user): array
    {
        return [
            'level' => $this->level($user),
            'xp' => $user->xp,
            'unlocked_categories' => $this->unlockedCategories($user),
        ];
    }
}
