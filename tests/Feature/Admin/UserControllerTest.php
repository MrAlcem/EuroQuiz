<?php

namespace Tests\Feature\Admin;

use App\Models\Result;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_index_can_search_users(): void
    {
        User::factory()->create(['name' => 'Visible Player']);
        User::factory()->create(['name' => 'Other Player']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/users?search=Visible');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.name', 'Visible Player');
    }

    public function test_admin_can_update_user_role_and_reset_mfa(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'two_factor_enabled' => true,
            'two_factor_secret' => 'secret',
        ]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->putJson("/api/admin/users/{$user->id}", [
                'name' => 'Updated Player',
                'role' => 'admin',
                'password' => 'new-password',
                'reset_mfa' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Player')
            ->assertJsonPath('data.role', 'admin');

        $user->refresh();
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertFalse($user->two_factor_enabled);
        $this->assertNull($user->two_factor_secret);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$admin->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
