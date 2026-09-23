<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_show_requires_authentication(): void
    {
        $response = $this->getJson('/api/user/profile');

        $response->assertUnauthorized();
    }

    public function test_show_returns_username_email_best_score_and_history(): void
    {
        $user = User::factory()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
        Result::factory()->for($user)->create(['score' => 30]);
        Result::factory()->for($user)->create(['score' => 70]);

        $response = $this->actingAs($user)->getJson('/api/user/profile');

        $response->assertOk();
        $response->assertJsonPath('username', 'Jane');
        $response->assertJsonPath('email', 'jane@example.com');
        $response->assertJsonPath('best_score', 70);
        $this->assertCount(2, $response->json('history'));
    }

    public function test_show_returns_the_users_level_based_on_total_score(): void
    {
        $user = User::factory()->create(['total_score' => 150]);

        $response = $this->actingAs($user)->getJson('/api/user/profile');

        $response->assertOk();
        $response->assertJsonPath('total_score', 150);
        $response->assertJsonPath('level', 'pro');
    }

    public function test_show_only_returns_the_authenticated_users_own_results(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Result::factory()->for($user)->create();
        Result::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->getJson('/api/user/profile');

        $response->assertOk();
        $this->assertCount(1, $response->json('history'));
    }

    public function test_show_defaults_best_score_to_zero_with_no_history(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user/profile');

        $response->assertOk();
        $response->assertJsonPath('best_score', 0);
        $this->assertSame([], $response->json('history'));
    }
}
