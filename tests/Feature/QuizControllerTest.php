<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_start_requires_authentication(): void
    {
        $response = $this->getJson('/api/quiz/start');

        $response->assertUnauthorized();
    }

    public function test_submit_requires_authentication(): void
    {
        $response = $this->postJson('/api/quiz/submit', ['answers' => []]);

        $response->assertUnauthorized();
    }

    public function test_start_returns_ten_random_questions_without_correct_option(): void
    {
        Question::factory()->count(15)->create();

        $response = $this->actingAs(User::factory()->create())->getJson('/api/quiz/start');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');

        $question = $response->json('data.0');
        $this->assertSame(['id', 'text', 'options', 'time_limit_seconds'], array_keys($question));
        $this->assertSame(['A', 'B', 'C', 'D'], array_keys($question['options']));
    }

    public function test_start_returns_each_questions_time_limit(): void
    {
        Question::factory()->timeLimit(20)->create();

        $response = $this->actingAs(User::factory()->create())->getJson('/api/quiz/start');

        $response->assertOk();
        $response->assertJsonPath('data.0.time_limit_seconds', 20);
    }

    public function test_submit_rejects_missing_answers(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/api/quiz/submit', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['answers']);
    }

    public function test_submit_rejects_invalid_chosen_option(): void
    {
        $question = Question::factory()->create();

        $response = $this->actingAs(User::factory()->create())->postJson('/api/quiz/submit', [
            'answers' => [
                ['question_id' => $question->id, 'chosen_option' => 'E'],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['answers.0.chosen_option']);
    }

    public function test_submit_rejects_unknown_question_id(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson('/api/quiz/submit', [
            'answers' => [
                ['question_id' => 999_999, 'chosen_option' => 'A'],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['answers.0.question_id']);
    }

    public function test_submit_rejects_duplicate_question_ids(): void
    {
        $question = Question::factory()->create();

        $response = $this->actingAs(User::factory()->create())->postJson('/api/quiz/submit', [
            'answers' => [
                ['question_id' => $question->id, 'chosen_option' => 'A'],
                ['question_id' => $question->id, 'chosen_option' => 'B'],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['answers.0.question_id']);
    }

    public function test_submit_scores_correct_answers_by_difficulty_and_ignores_client_supplied_correctness(): void
    {
        $easy = Question::factory()->difficulty('easy')->correctOption('A')->create();
        $medium = Question::factory()->difficulty('medium')->correctOption('B')->create();
        $hard = Question::factory()->difficulty('hard')->correctOption('C')->create();
        $user = User::factory()->create(['total_score' => 5]);

        $response = $this->actingAs($user)->postJson('/api/quiz/submit', [
            'answers' => [
                ['question_id' => $easy->id, 'chosen_option' => 'A', 'is_correct' => false],
                ['question_id' => $medium->id, 'chosen_option' => 'B'],
                ['question_id' => $hard->id, 'chosen_option' => 'C'],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'score' => 45,
            'correct_answers' => 3,
            'lives_remaining' => 3,
        ]);

        $this->assertDatabaseHas('results', [
            'user_id' => $user->id,
            'score' => 45,
            'correct_answers' => 3,
            'lives_remaining' => 3,
        ]);
        $this->assertSame(50, $user->refresh()->total_score);
    }

    public function test_submit_rejects_a_missing_chosen_option_key(): void
    {
        $question = Question::factory()->create();

        $response = $this->actingAs(User::factory()->create())->postJson('/api/quiz/submit', [
            'answers' => [
                ['question_id' => $question->id],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['answers.0.chosen_option']);
    }

    public function test_submit_treats_a_null_chosen_option_as_a_timed_out_wrong_answer(): void
    {
        $question = Question::factory()->correctOption('A')->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/quiz/submit', [
            'answers' => [
                ['question_id' => $question->id, 'chosen_option' => null],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'score' => 0,
            'correct_answers' => 0,
            'lives_remaining' => 2,
        ]);
    }

    public function test_submit_deducts_a_life_per_wrong_answer(): void
    {
        $question = Question::factory()->correctOption('A')->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/quiz/submit', [
            'answers' => [
                ['question_id' => $question->id, 'chosen_option' => 'B'],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'score' => 0,
            'correct_answers' => 0,
            'lives_remaining' => 2,
        ]);
    }

    public function test_submit_stops_scoring_once_lives_reach_zero(): void
    {
        $wrongAnswers = Question::factory()->correctOption('A')->count(3)->create();
        $uncountedCorrect = Question::factory()->difficulty('hard')->correctOption('A')->create();
        $user = User::factory()->create();

        $answers = $wrongAnswers->map(fn (Question $question) => [
            'question_id' => $question->id,
            'chosen_option' => 'B',
        ])->push([
            'question_id' => $uncountedCorrect->id,
            'chosen_option' => 'A',
        ])->all();

        $response = $this->actingAs($user)->postJson('/api/quiz/submit', ['answers' => $answers]);

        $response->assertOk();
        $response->assertJson([
            'score' => 0,
            'correct_answers' => 0,
            'lives_remaining' => 0,
        ]);
        $this->assertSame(0, $user->refresh()->total_score);
    }

    public function test_submit_counts_correct_answers_before_lives_run_out(): void
    {
        $correct = Question::factory()->difficulty('easy')->correctOption('A')->create();
        $wrongAnswers = Question::factory()->correctOption('A')->count(3)->create();
        $user = User::factory()->create();

        $answers = [[
            'question_id' => $correct->id,
            'chosen_option' => 'A',
        ], ...$wrongAnswers->map(fn (Question $question) => [
            'question_id' => $question->id,
            'chosen_option' => 'B',
        ])->all()];

        $response = $this->actingAs($user)->postJson('/api/quiz/submit', ['answers' => $answers]);

        $response->assertOk();
        $response->assertJson([
            'score' => 10,
            'correct_answers' => 1,
            'lives_remaining' => 0,
        ]);
    }
}
