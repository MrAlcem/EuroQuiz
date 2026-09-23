<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * A user row for the admin "user scores and activity" overview.
 *
 * @mixin User
 */
class AdminUserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'total_score' => $this->total_score,
            'quizzes_played' => $this->results_count,
            'last_active' => $this->results_max_created_at
                ? Carbon::parse($this->results_max_created_at)->toDateString()
                : null,
            'joined_at' => $this->created_at->toDateString(),
        ];
    }
}
