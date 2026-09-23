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

    public function test_store_creates_a_question(): void
    {
        $payload = [
            'question_text' => 'What is the capital of the Netherlands?',
            'option_a' => 'Rotterdam',
            'option_b' => 'Amsterdam',
            'option_c' => 'Utrecht',
            'option_d' => 'The Hague',
            'correct_option' => 'B',
            'category' => 'Geography',
            'country' => 'NL',
            'difficulty' => 'easy',
        ];

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/admin/questions', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.correct_option', 'B');
        $this->assertDatabaseHas('questions', ['question_text' => $payload['question_text']]);
    }

    public function test_store_rejects_non_admin_users(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson('/api/admin/questions', []);

        $response->assertForbidden();
    }

    public function test_store_rejects_an_invalid_correct_option(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->postJson('/api/admin/questions', [
            'question_text' => 'Question?',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
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
            ->putJson("/api/admin/questions/{$question->id}", ['question_text' => 'Updated text?']);

        $response->assertOk();
        $response->assertJsonPath('data.question_text', 'Updated text?');
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'question_text' => 'Updated text?']);
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
