<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuestionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/questions');

        $response->assertUnauthorized();
    }

    public function test_index_rejects_non_admin_users(): void
    {
        $response = $this->actingAs(User::factory()->create())->getJson('/api/admin/questions');

        $response->assertForbidden();
    }

    public function test_index_lists_questions_with_correct_option_for_admins(): void
    {
        Question::factory()->correctOption('B')->create();

        $response = $this->actingAs(User::factory()->admin()->create())->getJson('/api/admin/questions');

        $response->assertOk();
        $response->assertJsonPath('data.0.correct_option', 'B');
    }

    public function test_index_filters_by_category(): void
    {
        Question::factory()->create(['category' => 'Geography']);
        Question::factory()->create(['category' => 'History']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/questions?category=Geography');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.category', 'Geography');
    }

    public function test_index_filters_by_country(): void
    {
        Question::factory()->create(['country' => 'NL']);
        Question::factory()->create(['country' => 'HR']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/questions?country=HR');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.country', 'HR');
    }

    public function test_index_filters_by_category_and_country_together(): void
    {
        Question::factory()->create(['category' => 'Geography', 'country' => 'NL']);
        Question::factory()->create(['category' => 'Geography', 'country' => 'HR']);
        Question::factory()->create(['category' => 'History', 'country' => 'NL']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/questions?category=Geography&country=NL');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_searches_and_filters_by_difficulty(): void
    {
        Question::factory()->create([
            'question_text' => 'Which river crosses Europe?',
            'difficulty' => 'hard',
        ]);
        Question::factory()->create([
            'question_text' => 'What is a capital city?',
            'difficulty' => 'easy',
        ]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/questions?search=river&difficulty=hard');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.question_text', 'Which river crosses Europe?');
    }

    public function test_index_is_paginated(): void
    {
        Question::factory()->count(3)->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/questions?per_page=2&page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_store_creates_a_question(): void
    {
        $payload = [
            'question_text' => ['en' => 'What is the capital of the Netherlands?'],
            'option_a' => ['en' => 'Rotterdam'],
            'option_b' => ['en' => 'Amsterdam'],
            'option_c' => ['en' => 'Utrecht'],
            'option_d' => ['en' => 'The Hague'],
            'correct_option' => 'B',
            'category' => 'Geography',
            'country' => 'NL',
            'difficulty' => 'easy',
            'time_limit_seconds' => 20,
        ];

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/admin/questions', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.correct_option', 'B');
        $response->assertJsonPath('data.time_limit_seconds', 20);
        $this->assertSame(
            $payload['question_text']['en'],
            Question::find($response->json('data.id'))->question_text,
        );
    }

    public function test_store_defaults_the_time_limit_when_omitted(): void
    {
        $payload = [
            'question_text' => ['en' => 'Question?'],
            'option_a' => ['en' => 'A'],
            'option_b' => ['en' => 'B'],
            'option_c' => ['en' => 'C'],
            'option_d' => ['en' => 'D'],
            'correct_option' => 'A',
            'category' => 'General',
            'country' => 'NL',
            'difficulty' => 'easy',
        ];

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/admin/questions', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.time_limit_seconds', 15);
    }

    public function test_store_rejects_a_time_limit_outside_the_allowed_range(): void
    {
        $payload = [
            'question_text' => ['en' => 'Question?'],
            'option_a' => ['en' => 'A'],
            'option_b' => ['en' => 'B'],
            'option_c' => ['en' => 'C'],
            'option_d' => ['en' => 'D'],
            'correct_option' => 'A',
            'category' => 'General',
            'country' => 'NL',
            'difficulty' => 'easy',
            'time_limit_seconds' => 200,
        ];

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/admin/questions', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['time_limit_seconds']);
    }

    public function test_store_rejects_non_admin_users(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson('/api/admin/questions', []);

        $response->assertForbidden();
    }

    public function test_store_rejects_an_invalid_correct_option(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->postJson('/api/admin/questions', [
            'question_text' => ['en' => 'Question?'],
            'option_a' => ['en' => 'A'],
            'option_b' => ['en' => 'B'],
            'option_c' => ['en' => 'C'],
            'option_d' => ['en' => 'D'],
            'correct_option' => 'E',
            'category' => 'General',
            'country' => 'NL',
            'difficulty' => 'easy',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['correct_option']);
    }

    public function test_update_edits_a_question(): void
    {
        $question = Question::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->putJson("/api/admin/questions/{$question->id}", ['question_text' => ['en' => 'Updated text?']]);

        $response->assertOk();
        $response->assertJsonPath('data.question_text.en', 'Updated text?');
        $this->assertSame('Updated text?', $question->fresh()->question_text);
    }

    public function test_update_edits_the_time_limit(): void
    {
        $question = Question::factory()->timeLimit(15)->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->putJson("/api/admin/questions/{$question->id}", ['time_limit_seconds' => 30]);

        $response->assertOk();
        $response->assertJsonPath('data.time_limit_seconds', 30);
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'time_limit_seconds' => 30]);
    }

    public function test_destroy_deletes_a_question(): void
    {
        $question = Question::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->deleteJson("/api/admin/questions/{$question->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_destroy_rejects_non_admin_users(): void
    {
        $question = Question::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->deleteJson("/api/admin/questions/{$question->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }
}
