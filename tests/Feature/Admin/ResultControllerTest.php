<?php

namespace Tests\Feature\Admin;

use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ResultControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/admin/results')->assertUnauthorized();
    }

    public function test_index_rejects_non_admin_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/admin/results')
            ->assertForbidden();
    }

    public function test_index_lists_results_with_user_summary(): void
    {
        $user = User::factory()->create(['name' => 'Quiz Player']);
        Result::factory()->create(['user_id' => $user->id, 'score' => 125]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/results');

        $response->assertOk()
            ->assertJsonPath('data.0.user.name', 'Quiz Player')
            ->assertJsonPath('data.0.score', 125);
    }

    public function test_index_can_filter_by_player_and_daily_mode(): void
    {
        $matchingUser = User::factory()->create(['name' => 'Daily Player']);
        $otherUser = User::factory()->create(['name' => 'Other Player']);
        Result::factory()->create(['user_id' => $matchingUser->id, 'is_daily' => true]);
        Result::factory()->create(['user_id' => $otherUser->id, 'is_daily' => true]);
        Result::factory()->create(['user_id' => $matchingUser->id, 'is_daily' => false]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/results?search=Daily%20Player&daily=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.user.name', 'Daily Player');
    }

    public function test_show_returns_one_result(): void
    {
        $result = Result::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson("/api/admin/results/{$result->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $result->id)
            ->assertJsonPath('data.user.id', $result->user_id);
    }
}
