<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaderboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/leaderboard');

        $response->assertUnauthorized();
    }

    public function test_index_ranks_users_by_total_score_descending(): void
    {
        User::factory()->create(['name' => 'Low Scorer', 'total_score' => 10]);
        User::factory()->create(['name' => 'High Scorer', 'total_score' => 90]);
        $me = User::factory()->create(['name' => 'Me', 'total_score' => 50]);

        $response = $this->actingAs($me)->getJson('/api/leaderboard');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'High Scorer');
        $response->assertJsonPath('data.0.rank', 1);
        $response->assertJsonPath('data.1.name', 'Me');
        $response->assertJsonPath('data.1.rank', 2);
        $response->assertJsonPath('data.2.name', 'Low Scorer');
        $response->assertJsonPath('you.rank', 2);
        $response->assertJsonPath('you.score', 50);
    }

    public function test_index_reports_each_entrys_level(): void
    {
        User::factory()->create(['name' => 'Expert Player', 'total_score' => 300]);
        $me = User::factory()->create(['name' => 'Me', 'total_score' => 50]);

        $response = $this->actingAs($me)->getJson('/api/leaderboard');

        $response->assertOk();
        $response->assertJsonPath('data.0.level', 'expert');
        $response->assertJsonPath('you.level', 'beginner');
    }

    public function test_index_limits_to_top_twenty(): void
    {
        User::factory()->count(25)->create();
        $me = User::factory()->create();

        $response = $this->actingAs($me)->getJson('/api/leaderboard');

        $response->assertOk();
        $this->assertCount(20, $response->json('data'));
    }
}
