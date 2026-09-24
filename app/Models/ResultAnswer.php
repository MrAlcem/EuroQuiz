<?php

namespace App\Models;

use Database\Factories\ResultAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One answered question within a finished quiz `Result`, so a player can
 * review exactly what they answered afterwards.
 *
 * @property int $id
 * @property int $result_id
 * @property int $question_id
 * @property ?string $chosen_option
 * @property bool $is_correct
 * @property int $points_awarded
 */
#[Fillable(['result_id', 'question_id', 'chosen_option', 'is_correct', 'points_awarded'])]
class ResultAnswer extends Model
{
    /** @use HasFactory<ResultAnswerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    /**
     * @return BelongsTo<Result, $this>
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
