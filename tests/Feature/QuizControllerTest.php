<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class QuizControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_start_requires_authentication(): void
    {
        $response = $this->getJson('/api/quiz/start');

        $response->assertUnauthorized();
    }

    public function test_answer_requires_authentication(): void
    {
        $response = $this->postJson('/api/quiz/sessions/1/answer', ['chosen_option' => 'A']);

        $response->assertUnauthorized();
    }

    public function test_start_filters_by_country(): void
    {
        Question::factory()->count(3)->create(['country' => 'NL']);
        Question::factory()->count(3)->create(['country' => 'HR']);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/quiz/start?country=HR');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_start_rejects_a_category_not_unlocked_for_the_users_level(): void
    {
        Question::factory()->count(5)->create(['category' => 'History']);

        $response = $this->actingAs(User::factory()->create(['xp' => 0]))
            ->getJson('/api/quiz/start?category=History');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['category']);
    }

    public function test_start_orders_questions_from_easy_to_hard(): void
    {
        $easy = Question::factory()->difficulty('easy')->count(4)->create();
        $medium = Question::factory()->difficulty('medium')->count(3)->create();
        $hard = Question::factory()->difficulty('hard')->count(3)->create();

        $response = $this->actingAs(User::factory()->create())->getJson('/api/quiz/start');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertEqualsCanonicalizing($easy->pluck('id')->all(), $ids->slice(0, 4)->values()->all());
        $this->assertEqualsCanonicalizing($medium->pluck('id')->all(), $ids->slice(4, 3)->values()->all());
        $this->assertEqualsCanonicalizing($hard->pluck('id')->all(), $ids->slice(7, 3)->values()->all());
    }

    public function test_answer_accepts_a_null_chosen_option_as_a_timed_out_wrong_answer(): void
    {
        Question::factory()->correctOption('A')->create();
        $user = User::factory()->create();
        $sessionId = $this->actingAs($user)->getJson('/api/quiz/start')->json('session_id');

        $response = $this->actingAs($user)->postJson(
            "/api/quiz/sessions/{$sessionId}/answer",
            ['chosen_option' => null],
        );

        $response->assertOk();
        $response->assertJson(['correct' => false, 'lives_remaining' => 2]);
    }

    public function test_answer_awards_a_streak_bonus_for_three_consecutive_correct_answers(): void
    {
        Question::factory()->difficulty('easy')->correctOption('A')->count(3)->create();
        $user = User::factory()->create();
        $sessionId = $this->actingAs($user)->getJson('/api/quiz/start')->json('session_id');

        $last = $this->answerAll($user, $sessionId, count: 3, chosenOption: 'A');

        // 3 x 15 points for the easy questions, plus one 5-point streak bonus.
        $last->assertJson(['finished' => true, 'result' => ['score' => 50, 'correct_answers' => 3]]);
    }

    public function test_answer_resets_the_streak_after_a_wrong_answer(): void
    {
        $questions = Question::factory()->difficulty('easy')->correctOption('A')->count(7)->create();
        $user = User::factory()->create();
        $sessionId = $this->actingAs($user)->getJson('/api/quiz/start')->json('session_id');

        $last = null;
        foreach ($questions as $index => $question) {
            $chosen = $index === 3 ? 'B' : 'A';
            $last = $this->actingAs($user)->postJson(
                "/api/quiz/sessions/{$sessionId}/answer",
                ['chosen_option' => $chosen],
            );
        }

        // Two streaks of 3 correct answers (before and after the wrong
        // answer at index 3), each earning the 5-point bonus: 6 x 15 + 2 x 5.
        $last->assertJson([
            'finished' => true,
            'result' => ['score' => 100, 'correct_answers' => 6, 'lives_remaining' => 2],
        ]);
    }

    public function test_daily_gives_different_questions_to_different_users(): void
    {
        Question::factory()->difficulty('easy')->count(8)->create();
        Question::factory()->difficulty('medium')->count(6)->create();
        Question::factory()->difficulty('hard')->count(6)->create();

        $first = $this->actingAs(User::factory()->create())->getJson('/api/quiz/daily');
        $second = $this->actingAs(User::factory()->create())->getJson('/api/quiz/daily');

        $firstIds = collect($first->json('data'))->pluck('id')->sort()->values();
        $secondIds = collect($second->json('data'))->pluck('id')->sort()->values();

        $this->assertNotSame($firstIds->all(), $secondIds->all());
    }

    public function test_daily_reports_already_completed_with_result_and_reset_time(): void
    {
        Question::factory()->difficulty('easy')->correctOption('A')->create();
        $user = User::factory()->create();

        $start = $this->actingAs($user)->getJson('/api/quiz/daily');
        $start->assertJsonPath('already_completed', false);
        $start->assertJsonPath('completed_result', null);

        $sessionId = $start->json('session_id');
        $this->actingAs($user)->postJson("/api/quiz/sessions/{$sessionId}/answer", ['chosen_option' => 'A']);

        $response = $this->actingAs($user)->getJson('/api/quiz/daily');

        $response->assertOk();
        $response->assertJsonPath('already_completed', true);
        // 15 points for the easy question, plus the 20-point daily bonus.
        $response->assertJsonPath('completed_result.score', 35);
        $this->assertNotNull($response->json('completed_result.completed_at'));
        $this->assertNotNull($response->json('resets_at'));
        $this->assertSame(35, $user->refresh()->total_score);
    }

    /**
     * Answer the given user's active session `count` times with the same
     * chosen option and return the final response.
     */
    private function answerAll(User $user, int $sessionId, int $count, string $chosenOption): TestResponse
    {
        $response = null;

        for ($i = 0; $i < $count; $i++) {
            $response = $this->actingAs($user)->postJson(
                "/api/quiz/sessions/{$sessionId}/answer",
                ['chosen_option' => $chosenOption],
            );
        }

        return $response;
    }
}
