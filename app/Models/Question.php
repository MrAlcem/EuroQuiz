<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $question_text
 * @property string $option_a
 * @property string $option_b
 * @property string $option_c
 * @property string $option_d
 * @property string $correct_option
 * @property string $category
 * @property string $country
 * @property string $difficulty
 * @property int $time_limit_seconds
 */
#[Fillable(['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option', 'category', 'country', 'difficulty', 'time_limit_seconds'])]
#[Hidden(['correct_option'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'time_limit_seconds' => 15,
    ];
}
