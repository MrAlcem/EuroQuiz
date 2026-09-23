<?php

namespace App\Models;

use Database\Factories\DailyChallengeAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Records that a user completed the daily challenge on a given date, so
 * they cannot claim the daily bonus more than once per day.
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $challenge_date
 * @property int $result_id
 */
#[Fillable(['user_id', 'challenge_date', 'result_id'])]
class DailyChallengeAttempt extends Model
{
    /** @use HasFactory<DailyChallengeAttemptFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Result, $this>
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'challenge_date' => 'date',
        ];
    }
}
