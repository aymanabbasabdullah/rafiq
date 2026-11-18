<?php

namespace Tests\Feature;

use App\Console\Commands\ContentReviewRemindersCommand;
use App\Enums\AiMode;
use App\Enums\AiOutcome;
use App\Enums\PublishStatus;
use App\Models\AiUsageLog;
use App\Models\ArticleTranslation;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Module-12 scheduled tasks: retention clean-up (PRIV-011), periodic review reminders (SAFE-013)
 * and the AI budget alert at 80% of the daily cap (M12-FR-05).
 */
class ScheduledTasksTest extends TestCase
{
    use RefreshDatabase;

    private function aiLog(): AiUsageLog
    {
        return AiUsageLog::query()->create([
            'created_at' => now(),
            'mode' => AiMode::GeneralEducation,
            'locale' => 'en',
            'outcome' => AiOutcome::Answered,
            'ai_configuration_version' => 0,
            'core_prompt_version' => 'v1',
            'model' => 'test-model',
        ]);
    }

    public function test_prune_deletes_only_records_older_than_their_retention_period(): void
    {
        $this->travel(-91)->days();
        $this->aiLog();
        AuditLog::query()->create(['action' => 'old.action', 'summary' => [], 'created_at' => now()]);
        $this->travelBack();

        $this->travel(-89)->days();
        $this->aiLog();
        $this->travelBack();

        $this->travel(-731)->days();
        AuditLog::query()->create(['action' => 'very.old.action', 'summary' => [], 'created_at' => now()]);
        $this->travelBack();

        $this->artisan('rafiq:prune', ['--chunk' => 1])
            ->expectsOutput('ai_usage_logs: 1')
            ->expectsOutput('audit_logs: 1')
            ->assertSuccessful();

        $this->assertSame(1, AiUsageLog::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'old.action']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'very.old.action']);
    }

    public function test_review_reminder_is_recorded_once_a_day_when_published_content_is_due(): void
    {
        $this->artisan('rafiq:content:review-reminders')->expectsOutput('Overdue: 0')->assertSuccessful();
        $this->assertDatabaseMissing('audit_logs', ['action' => ContentReviewRemindersCommand::ACTION]);

        ArticleTranslation::factory()->create(['publish_status' => PublishStatus::Published, 'review_due_at' => now()->subDay()]);
        ArticleTranslation::factory()->create(['publish_status' => PublishStatus::Published, 'review_due_at' => now()->addDays(10)]);
        ArticleTranslation::factory()->create(['publish_status' => PublishStatus::Draft, 'review_due_at' => now()->subDay()]);

        $this->artisan('rafiq:content:review-reminders')
            ->expectsOutput('Overdue: 1')
            ->expectsOutput('Due within 30 days: 1')
            ->assertSuccessful();
        $this->artisan('rafiq:content:review-reminders')->assertSuccessful();

        $reminders = AuditLog::query()->where('action', ContentReviewRemindersCommand::ACTION)->get();
        $this->assertCount(1, $reminders);
        $this->assertSame(['overdue' => 1, 'due_soon' => 1, 'window_days' => 30], $reminders->first()?->summary);
    }

    public function test_budget_check_warns_once_at_eighty_percent_of_the_daily_call_cap(): void
    {
        config(['rafiq.ai.enabled' => true, 'rafiq.ai.pricing.input_per_million_usd' => 0, 'rafiq.ai.pricing.output_per_million_usd' => 0]);
        Log::spy();

        $this->artisan('rafiq:ai:budget-check')->expectsOutput('AI daily cap used: 0%')->assertSuccessful();

        for ($i = 0; $i < 240; $i++) {
            $this->aiLog();
        }

        $this->artisan('rafiq:ai:budget-check')->expectsOutput('AI daily cap used: 80%')->assertSuccessful();
        $this->artisan('rafiq:ai:budget-check')->assertSuccessful();

        Log::shouldHaveReceived('warning')->once()->with('Rafiq AI daily cap almost reached', ['used_percent' => 80]);
    }
}
