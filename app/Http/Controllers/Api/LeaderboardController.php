<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaderboardEntryResource;
use App\Models\User;
use App\UserLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Exposes the global leaderboard, ranked by total score.
 */
class LeaderboardController extends Controller
{
    private const TOP_LIMIT = 20;

    /**
     * List the top-ranked users, and the requesting user's own rank.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $topUsers = User::query()
            ->orderByDesc('total_score')
            ->orderBy('name')
            ->limit(self::TOP_LIMIT)
            ->get();

        $entries = $topUsers->values()->map(fn (User $user, int $index) => (object) [
            'rank' => $index + 1,
            'name' => $user->name,
            'score' => $user->total_score,
        ]);

        $you = $this->rankFor($request->user());

        return LeaderboardEntryResource::collection($entries)->additional(['you' => $you]);
    }

    /**
     * Compute the given user's own rank and score, regardless of whether
     * they made the top of the leaderboard.
     *
     * @return array{rank: int, name: string, score: int, level: string}
     */
    private function rankFor(User $user): array
    {
        $rank = User::query()
            ->where('total_score', '>', $user->total_score)
            ->count() + 1;

        return [
            'rank' => $rank,
            'name' => $user->name,
            'score' => $user->total_score,
            'level' => UserLevel::fromScore($user->total_score)->value,
        ];
    }
}
