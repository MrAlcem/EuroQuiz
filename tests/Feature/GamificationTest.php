<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_start_returns_a_stateful_session_and_gamification_summary(): void
    {
        Question::factory()->count(10)->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/quiz/start');

        $response->assertOk()->assertJsonStructure([
            'data', 'session_id', 'timer_seconds',
            'gamification' => ['level', 'xp', 'unlocked_categories'],
        ]);
        $response->assertJsonPath('timer_seconds', 30);
        $response->assertJsonPath('gamification.level', 'Beginner');
    }

    public function test_session_answer_scores_and_returns_the_next_question(): void
    {
        Question::factory()->count(10)->create([
            'difficulty' => 'easy',
            'correct_option' => 'A',
        ]);
        $user = User::factory()->create();
        $start = $this->actingAs($user)->getJson('/api/quiz/start');

        $response = $this->actingAs($user)->postJson(
            '/api/quiz/sessions/'.$start->json('session_id').'/answer',
            ['chosen_option' => 'A'],
        );

        $response->assertOk()->assertJson([
            'correct' => true,
            'timed_out' => false,
            'lives_remaining' => 3,
            'finished' => false,
        ])->assertJsonStructure(['next_question']);
    }

    public function test_expired_question_is_counted_as_wrong(): void
    {
        Question::factory()->count(10)->create(['correct_option' => 'A']);
        $user = User::factory()->create();
        $start = $this->actingAs($user)->getJson('/api/quiz/start');

        Carbon::setTestNow(now()->addSeconds(31));
        $response = $this->actingAs($user)->postJson(
            '/api/quiz/sessions/'.$start->json('session_id').'/answer',
            ['chosen_option' => 'A'],
        );
        Carbon::setTestNow();

        $response->assertOk()->assertJson([
            'correct' => false,
            'timed_out' => true,
            'lives_remaining' => 2,
        ]);
    }

    public function test_daily_challenge_is_reused_for_the_same_user_and_date(): void
    {
        Question::factory()->count(10)->create();
        $user = User::factory()->create();

        $first = $this->actingAs($user)->getJson('/api/quiz/daily');
        $second = $this->actingAs($user)->getJson('/api/quiz/daily');

        $this->assertSame($first->json('session_id'), $second->json('session_id'));
        $first->assertJsonPath('daily', true);
    }
}
