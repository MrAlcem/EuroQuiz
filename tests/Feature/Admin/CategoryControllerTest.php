<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_categories_require_an_admin(): void
    {
        $this->getJson('/api/admin/categories')->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->getJson('/api/admin/categories')
            ->assertForbidden();
    }

    public function test_admin_can_list_categories_with_question_counts(): void
    {
        $category = Category::create(['name' => 'Science']);
        Question::factory()->create(['category' => 'Science']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/categories');

        $response->assertOk();
        $entry = collect($response->json('data'))->firstWhere('name', 'Science');
        $this->assertSame(1, $entry['question_count']);
    }

    public function test_admin_can_create_and_rename_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/categories', ['name' => 'Science'])
            ->assertCreated()
            ->json('data');

        $question = Question::factory()->create(['category' => 'Science']);

        $this->actingAs($admin)
            ->putJson("/api/admin/categories/{$created['id']}", ['name' => 'Technology'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Technology');

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'category' => 'Technology',
        ]);
    }

    public function test_category_with_questions_requires_a_replacement_category(): void
    {
        $category = Category::create(['name' => 'Science']);
        Question::factory()->create(['category' => 'Science']);

        $this->actingAs(User::factory()->admin()->create())
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_with_questions_can_be_deleted_after_reassignment(): void
    {
        $category = Category::create(['name' => 'Science']);
        $replacement = Category::create(['name' => 'Technology']);
        $question = Question::factory()->create(['category' => 'Science']);

        $this->actingAs(User::factory()->admin()->create())
            ->deleteJson("/api/admin/categories/{$category->id}", [
                'replacement_category' => $replacement->name,
            ])
            ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'category' => 'Technology',
        ]);
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $category = Category::create(['name' => 'Science']);

        $this->actingAs(User::factory()->admin()->create())
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
