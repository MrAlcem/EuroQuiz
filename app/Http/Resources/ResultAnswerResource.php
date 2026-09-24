<?php

namespace App\Http\Resources;

use App\Models\ResultAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One answered question within a finished result, for post-quiz review.
 * The quiz is over, so `correct_option` is safe to reveal here (unlike
 * `QuestionResource`, used while a quiz is still in progress).
 *
 * @mixin ResultAnswer
 */
class ResultAnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'question_id' => $this->question_id,
            'question_text' => $this->question->question_text,
            'options' => [
                'A' => $this->question->option_a,
                'B' => $this->question->option_b,
                'C' => $this->question->option_c,
                'D' => $this->question->option_d,
            ],
            'chosen_option' => $this->chosen_option,
            'correct_option' => $this->question->correct_option,
            'is_correct' => $this->is_correct,
            'points_awarded' => $this->points_awarded,
        ];
    }
}
