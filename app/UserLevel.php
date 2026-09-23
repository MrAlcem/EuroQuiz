<?php

namespace App;

enum UserLevel: string
{
    case Beginner = 'beginner';
    case Pro = 'pro';
    case Expert = 'expert';

    /**
     * @var array<string, int>
     */
    private const MINIMUM_SCORE = [
        'expert' => 300,
        'pro' => 100,
    ];

    /**
     * Determine the level a user has reached for a given total score.
     */
    public static function fromScore(int $totalScore): self
    {
        return match (true) {
            $totalScore >= self::MINIMUM_SCORE['expert'] => self::Expert,
            $totalScore >= self::MINIMUM_SCORE['pro'] => self::Pro,
            default => self::Beginner,
        };
    }
}
