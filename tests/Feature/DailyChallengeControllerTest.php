<?php

namespace Tests\Feature;

use App\Models\DailyChallengeAttempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DailyChallengeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_show_requires_authentication(): void
    {
        $response = $this->getJson('/api/quiz/daily');

        $response->assertUnauthorized();
    }

    public function test_show_returns_ten_questions_and_not_completed(): void
    {
        Question::factory()->count(15)->create();

        $response = $this->actingAs(User::factory()->create())->getJson('/api/quiz/daily');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('already_completed', false);
    }

    public function test_show_returns_the_same_questions_to_every_player_today(): void
    {
        Question::factory()->count(20)->create();

        $first = $this->actingAs(User::factory()->create())->getJson('/api/quiz/daily');
        $second = $this->actingAs(User::factory()->create())->getJson('/api/quiz/daily');

        $firstIds = collect($first->json('data'))->pluck('id')->sort()->values();
        $secondIds = collect($second->json('data'))->pluck('id')->sort()->values();

        $this->assertSame($firstIds->all(), $secondIds->all());
    }

    public function test_show_reports_already_completed_after_a_submission(): void
    {
        $questions = Question::factory()->count(10)->create();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/quiz/daily/submit', [
            'answers' => $questions->map(fn (Question $question) => [
                'question_id' => $question->id,
                'chosen_option' => null,
            ])->all(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/quiz/daily');

        $response->assertOk();
        $response->assertJsonPath('already_completed', true);
    }

    public function test_submit_requires_authentication(): void
    {
        $response = $this->postJson('/api/quiz/daily/submit', ['answers' => []]);

        $response->assertUnauthorized();
    }

    public function test_submit_awards_the_daily_bonus_on_top_of_the_normal_score(): void
    {
        $question = Question::factory()->difficulty('easy')->correctOption('A')->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/quiz/daily/submit', [
            'answers' => [
                ['question_id' => $question->id, 'chosen_option' => 'A'],
            ],
        ]);

        $response->assertOk();
        // 15 points for the easy question, plus the 20-point daily bonus.
        $response->assertJson(['score' => 35, 'correct_answers' => 1, 'lives_remaining' => 3]);
        $this->assertSame(35, $user->refresh()->total_score);
    }

    public function test_submit_records_the_attempt_for_today(): void
    {
        $question = Question::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/quiz/daily/submit', [
            'answers' => [['question_id' => $question->id, 'chosen_option' => null]],
        ]);

        $this->assertTrue(
            DailyChallengeAttempt::query()
                ->where('user_id', $user->id)
                ->whereDate('challenge_date', now())
                ->exists()
        );
    }

    public function test_submit_rejects_a_second_attempt_on_the_same_day(): void
    {
        $question = Question::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/quiz/daily/submit', [
            'answers' => [['question_id' => $question->id, 'chosen_option' => null]],
        ]);
        $scoreAfterFirstAttempt = $user->refresh()->total_score;

        $response = $this->actingAs($user)->postJson('/api/quiz/daily/submit', [
            'answers' => [['question_id' => $question->id, 'chosen_option' => null]],
        ]);

        $response->assertStatus(409);
        $this->assertSame($scoreAfterFirstAttempt, $user->refresh()->total_score);
        $this->assertSame(1, DailyChallengeAttempt::where('user_id', $user->id)->count());
    }
}
