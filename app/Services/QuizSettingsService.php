<?php

namespace App\Services;

use App\Models\QuizSetting;

class QuizSettingsService
{
    /**
     * @return array<string, int>
     */
    public function all(): array
    {
        return QuizSetting::query()->pluck('value', 'key')->map(
            fn (mixed $value): int => (int) $value,
        )->all();
    }

    public function get(string $key): int
    {
        return (int) QuizSetting::query()->where('key', $key)->value('value');
    }

    public function quizLength(): int
    {
        return $this->get('quiz_length');
    }

    public function lives(): int
    {
        return $this->get('lives');
    }

    public function timerSeconds(): int
    {
        return $this->get('timer_seconds');
    }

    /**
     * @return array<string, int>
     */
    public function questionsPerDifficulty(): array
    {
        return [
            'easy' => $this->get('easy_questions'),
            'medium' => $this->get('medium_questions'),
            'hard' => $this->get('hard_questions'),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function pointsByDifficulty(): array
    {
        return [
            'easy' => $this->get('easy_points'),
            'medium' => $this->get('medium_points'),
            'hard' => $this->get('hard_points'),
        ];
    }
}
