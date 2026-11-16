<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Module-11-E: aggregated counters for every admin role, small counts hidden server-side,
 * a fixed period and only allow-listed metrics.
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function metric(string $metric, string $dimension, int $value, int $daysAgo = 0): void
    {
        DB::table('daily_metrics')->insert([
            'date' => now()->subDays($daysAgo)->toDateString(),
            'metric' => $metric,
            'dimension' => $dimension,
            'value' => $value,
        ]);
    }

    public function test_every_admin_role_can_view_analytics_but_accounts_without_a_role_cannot(): void
    {
        foreach ([User::factory()->superAdmin(), User::factory()->contentEditor(), User::factory()->medicalReviewer()] as $factory) {
            $this->actingAs($factory->create())->get('/admin/analytics')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Analytics/Index')
                ->where('admin.user.canViewAnalytics', true));
        }

        $this->actingAs(User::factory()->create())->get('/admin/analytics')->assertForbidden();
    }

    public function test_counts_below_five_are_hidden_before_they_reach_the_browser(): void
    {
        $this->metric('page_view', 'landing', 12);
        $this->metric('page_view', 'article', 3);
        $this->metric('age_gate', 'under18', 4);
        $this->metric('age_gate', 'adult', 5);

        $this->actingAs(User::factory()->superAdmin()->create())->get('/admin/analytics')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('report.period', 30)
            ->where('report.totals.page_view', 15)
            ->where('report.totals.age_gate', 9)
            ->where('report.sections.0.metric', 'page_view')
            ->where('report.sections.0.rows', [['dimension' => 'landing', 'value' => 12], ['dimension' => 'article', 'value' => null]])
            ->where('report.sections.3.rows', [['dimension' => 'adult', 'value' => 5], ['dimension' => 'under18', 'value' => null]])
            ->has('report.daily', 30)
            ->where('report.daily.29.value', 15));
    }

    public function test_the_period_limits_the_rows_and_unknown_periods_fall_back_to_thirty_days(): void
    {
        $this->metric('page_view', 'landing', 6, daysAgo: 2);
        $this->metric('page_view', 'landing', 7, daysAgo: 20);

        $admin = User::factory()->contentEditor()->create();

        $this->actingAs($admin)->get('/admin/analytics?period=7')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('report.period', 7)
            ->has('report.daily', 7)
            ->where('report.totals.page_view', 6));

        $this->actingAs($admin)->get('/admin/analytics?period=365')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('report.period', 30)
            ->where('report.totals.page_view', 13));
    }

    public function test_only_allow_listed_metrics_are_shown_and_explained_articles_are_ranked(): void
    {
        $this->metric('priority', 'urgent', 50);
        $this->metric('red_flag', 'rf_001', 50);
        $this->metric('article_ai_explain', 'article:999', 8);

        $this->actingAs(User::factory()->superAdmin()->create())->get('/admin/analytics')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('report.sections', 7)
            ->missing('report.totals.priority')
            ->missing('report.totals.red_flag')
            ->where('report.topArticles', [['id' => 999, 'title' => '#999', 'value' => 8]]));
    }
}
