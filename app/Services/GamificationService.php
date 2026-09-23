<?php

namespace App\Services;

use App\Models\User;
use App\UserLevel;

class GamificationService
{
    public const TIMER_SECONDS = 30;

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
        return match ($this->level($user)) {
            'Expert' => ['Geography', 'Nature', 'History', 'Culture'],
            'Pro' => ['Geography', 'Nature', 'History'],
            default => ['Geography', 'Nature'],
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
