<?php

namespace App\Services;

use App\Models\User;

class GamificationService
{
    public const TIMER_SECONDS = 30;

    public function level(User $user): string
    {
        return match (true) {
            $user->xp >= 2500 => 'Expert',
            $user->xp >= 1000 => 'Pro',
            default => 'Beginner',
        };
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
