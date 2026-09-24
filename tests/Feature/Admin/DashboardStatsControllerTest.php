<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Question;
use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardStatsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_stats_require_an_admin(): void
    {
        $this->getJson('/api/admin/dashboard/stats')->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->getJson('/api/admin/dashboard/stats')
            ->assertForbidden();
    }

    public function test_dashboard_stats_return_totals_distributions_and_seven_day_activity(): void
    {
        Category::create(['name' => 'Science']);
        Question::factory()->count(2)->create(['category' => 'Science', 'difficulty' => 'easy']);
        $user = User::factory()->create();
        Result::factory()->create(['user_id' => $user->id, 'score' => 50, 'created_at' => Carbon::today()]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/dashboard/stats');

        $response->assertOk()
            ->assertJsonPath('data.totals.questions', 2)
            ->assertJsonPath('data.totals.results', 1)
            ->assertJsonPath('data.totals.score', 50)
            ->assertJsonPath('data.questions.by_category.0.label', 'Science');
        $this->assertCount(7, $response->json('data.activity'));
    }
}
