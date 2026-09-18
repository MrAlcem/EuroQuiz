<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Exposes a question for answering, deliberately omitting `correct_option`.
 *
 * @mixin Question
 */
class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->question_text,
            'options' => [
                'A' => $this->option_a,
                'B' => $this->option_b,
                'C' => $this->option_c,
                'D' => $this->option_d,
            ],
        ];
    }
}
