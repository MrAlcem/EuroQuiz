<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyChallenge extends Model
{
    protected $fillable = ['challenge_date', 'question_ids'];

    protected function casts(): array
    {
        return [
            'challenge_date' => 'date',
            'question_ids' => 'array',
        ];
    }
}
