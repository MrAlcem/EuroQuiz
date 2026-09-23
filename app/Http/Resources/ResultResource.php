<?php

namespace App\Http\Resources;

use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Result
 */
class ResultResource extends JsonResource
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
            'score' => $this->score,
            'correct_answers' => $this->correct_answers,
            'lives_remaining' => $this->lives_remaining,
            'date' => $this->created_at->toDateString(),
        ];
    }
}
