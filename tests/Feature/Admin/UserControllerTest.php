<?php

namespace Tests\Feature\Admin;

use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/users');

        $response->assertUnauthorized();
    }

    public function test_index_rejects_non_admin_users(): void
    {
        $response = $this->actingAs(User::factory()->create())->getJson('/api/admin/users');

        $response->assertForbidden();
    }

    public function test_index_lists_users_ordered_by_total_score_descending(): void
    {
        User::factory()->create(['name' => 'Low Scorer', 'total_score' => 10]);
        User::factory()->create(['name' => 'High Scorer', 'total_score' => 90]);

        $response = $this->actingAs(User::factory()->admin()->create())->getJson('/api/admin/users');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'High Scorer');
        $response->assertJsonPath('data.0.total_score', 90);
    }

    public function test_index_reports_each_users_level(): void
    {
        User::factory()->create(['name' => 'Expert Player', 'total_score' => 300]);
        User::factory()->create(['name' => 'Newcomer', 'total_score' => 0]);

        $response = $this->actingAs(User::factory()->admin()->create())->getJson('/api/admin/users');

        $data = collect($response->json('data'));
        $response->assertOk();
        $this->assertSame('expert', $data->firstWhere('name', 'Expert Player')['level']);
        $this->assertSame('beginner', $data->firstWhere('name', 'Newcomer')['level']);
    }

    public function test_index_reports_quiz_count_and_last_activity(): void
    {
        $user = User::factory()->create();
        Result::factory()->for($user)->create(['created_at' => '2026-09-01']);
        Result::factory()->for($user)->create(['created_at' => '2026-09-20']);

        $response = $this->actingAs(User::factory()->admin()->create())->getJson('/api/admin/users');

        $response->assertOk();
        $entry = collect($response->json('data'))->firstWhere('id', $user->id);
        $this->assertSame(2, $entry['quizzes_played']);
        $this->assertSame('2026-09-20', $entry['last_active']);
    }

    public function test_index_reports_null_last_active_with_no_quizzes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->getJson('/api/admin/users');

        $response->assertOk();
        $entry = collect($response->json('data'))->firstWhere('id', $user->id);
        $this->assertSame(0, $entry['quizzes_played']);
        $this->assertNull($entry['last_active']);
    }
}
