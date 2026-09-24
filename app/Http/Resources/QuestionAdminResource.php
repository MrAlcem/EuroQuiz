<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full question, including `correct_option`, for admin management.
 *
 * `question_text` and the options are exposed as `{locale: text}` maps (all
 * translations at once) so the admin panel can edit every language.
 *
 * @mixin Question
 */
class QuestionAdminResource extends JsonResource
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
            'question_text' => $this->getTranslations('question_text'),
            'option_a' => $this->getTranslations('option_a'),
            'option_b' => $this->getTranslations('option_b'),
            'option_c' => $this->getTranslations('option_c'),
            'option_d' => $this->getTranslations('option_d'),
            'correct_option' => $this->correct_option,
            'category' => $this->category,
            'country' => $this->country,
            'difficulty' => $this->difficulty,
            'time_limit_seconds' => $this->time_limit_seconds,
        ];
    }
}
