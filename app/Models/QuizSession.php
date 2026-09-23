<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizSession extends Model
{
    protected $fillable = [
        'user_id', 'question_ids', 'current_question_index', 'lives_remaining',
        'score', 'correct_answers', 'status', 'mode', 'daily_date', 'question_started_at',
    ];

    protected function casts(): array
    {
        return [
            'question_ids' => 'array',
            'daily_date' => 'date',
            'question_started_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
