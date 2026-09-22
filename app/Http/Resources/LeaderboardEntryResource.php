<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single ranked entry on the leaderboard.
 *
 * @property int $rank
 * @property string $name
 * @property int $score
 */
class LeaderboardEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->rank,
            'name' => $this->name,
            'score' => $this->score,
        ];
    }
}
