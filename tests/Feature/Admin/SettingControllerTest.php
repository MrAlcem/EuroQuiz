<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_settings_require_an_admin(): void
    {
        $this->getJson('/api/admin/settings')->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->getJson('/api/admin/settings')
            ->assertForbidden();
    }

    public function test_admin_can_read_default_settings(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/settings');

        $response->assertOk()
            ->assertJsonPath('data.quiz_length', 10)
            ->assertJsonPath('data.timer_seconds', 30)
            ->assertJsonPath('data.daily_bonus', 20);
    }

    public function test_admin_can_update_settings(): void
    {
        $payload = [
            'quiz_length' => 8,
            'lives' => 5,
            'timer_seconds' => 45,
            'easy_questions' => 3,
            'medium_questions' => 3,
            'hard_questions' => 2,
            'easy_points' => 10,
            'medium_points' => 20,
            'hard_points' => 35,
            'streak_length' => 4,
            'streak_bonus' => 8,
            'daily_bonus' => 25,
        ];

        $response = $this->actingAs(User::factory()->admin()->create())
            ->putJson('/api/admin/settings', $payload);

        $response->assertOk()->assertJsonPath('data.quiz_length', 8);
        $this->assertSame(45, DB::table('quiz_settings')->where('key', 'timer_seconds')->value('value'));
    }

    public function test_question_distribution_must_match_quiz_length(): void
    {
        $payload = [
            'quiz_length' => 10,
            'lives' => 3,
            'timer_seconds' => 30,
            'easy_questions' => 4,
            'medium_questions' => 4,
            'hard_questions' => 4,
            'easy_points' => 15,
            'medium_points' => 25,
            'hard_points' => 40,
            'streak_length' => 3,
            'streak_bonus' => 5,
            'daily_bonus' => 20,
        ];

        $this->actingAs(User::factory()->admin()->create())
            ->putJson('/api/admin/settings', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Easy, medium and hard question counts must add up to the quiz length.');
    }
}
