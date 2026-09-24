<?php

namespace App\Models;

use Database\Factories\ResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $score
 * @property int $correct_answers
 * @property int $lives_remaining
 */
#[Fillable(['user_id', 'quiz_session_id', 'score', 'xp_earned', 'is_daily', 'correct_answers', 'lives_remaining'])]
class Result extends Model
{
    /** @use HasFactory<ResultFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_daily' => 'boolean'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ResultAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ResultAnswer::class);
    }
}
