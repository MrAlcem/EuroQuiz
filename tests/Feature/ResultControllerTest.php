<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ResultControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_show_requires_authentication(): void
    {
        $result = Result::factory()->create();

        $response = $this->getJson("/api/results/{$result->id}");

        $response->assertUnauthorized();
    }

    public function test_show_rejects_viewing_another_users_result(): void
    {
        $result = Result::factory()->create();

        $response = $this->actingAs(User::factory()->create())->getJson("/api/results/{$result->id}");

        $response->assertForbidden();
    }

    public function test_show_returns_the_result_with_its_answer_log_in_order(): void
    {
        $correct = Question::factory()->difficulty('easy')->correctOption('A')->create(['question_text' => 'Correct one?']);
        $wrong = Question::factory()->difficulty('easy')->correctOption('A')->create(['question_text' => 'Wrong one?']);
        $user = User::factory()->create();
        $sessionId = $this->actingAs($user)->getJson('/api/quiz/start')->json('session_id');

        // Only these two questions exist, so the session is exactly these
        // two in the order QuizQuestionSelector picked them.
        $questionOrder = collect(QuizSession::find($sessionId)->question_ids);

        $lastResponse = null;
        foreach ($questionOrder as $questionId) {
            $chosen = $questionId === $wrong->id ? 'B' : 'A';
            $lastResponse = $this->actingAs($user)->postJson(
                "/api/quiz/sessions/{$sessionId}/answer",
                ['chosen_option' => $chosen],
            );
        }

        $resultId = $lastResponse->json('result.id');

        $response = $this->actingAs($user)->getJson("/api/results/{$resultId}");

        $response->assertOk();
        $response->assertJsonCount(2, 'answers');

        $answersByQuestionId = collect($response->json('answers'))->keyBy('question_id');

        $correctAnswer = $answersByQuestionId->get($correct->id);
        $this->assertSame('Correct one?', $correctAnswer['question_text']);
        $this->assertSame('A', $correctAnswer['chosen_option']);
        $this->assertSame('A', $correctAnswer['correct_option']);
        $this->assertTrue($correctAnswer['is_correct']);
        $this->assertSame(15, $correctAnswer['points_awarded']);

        $wrongAnswer = $answersByQuestionId->get($wrong->id);
        $this->assertSame('B', $wrongAnswer['chosen_option']);
        $this->assertFalse($wrongAnswer['is_correct']);
        $this->assertSame(0, $wrongAnswer['points_awarded']);
    }

    public function test_show_logs_a_null_chosen_option_for_a_timed_out_answer(): void
    {
        Question::factory()->create();
        $user = User::factory()->create();
        $sessionId = $this->actingAs($user)->getJson('/api/quiz/start')->json('session_id');

        $last = $this->actingAs($user)->postJson(
            "/api/quiz/sessions/{$sessionId}/answer",
            ['chosen_option' => null],
        );

        $response = $this->actingAs($user)->getJson('/api/results/'.$last->json('result.id'));

        $response->assertOk();
        $response->assertJsonPath('answers.0.chosen_option', null);
        $response->assertJsonPath('answers.0.is_correct', false);
    }
}
