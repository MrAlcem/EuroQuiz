<?php

namespace App\Http\Resources;

use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Result
 */
class AdminResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'score' => $this->score,
            'correct_answers' => $this->correct_answers,
            'lives_remaining' => $this->lives_remaining,
            'xp_earned' => $this->xp_earned,
            'daily' => $this->is_daily,
            'date' => $this->created_at->toIso8601String(),
        ];
    }
}
