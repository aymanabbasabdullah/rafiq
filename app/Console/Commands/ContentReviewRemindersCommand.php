<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PublishStatus;
use App\Models\ArticleTranslation;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Command;

/**
 * Daily periodic-review reminder (SAFE-013, M12-FR-02). Counts published translations whose medical review
 * is due within the window or already overdue, and records an in-panel notice in the audit log (shown on
 * the admin dashboard's activity list) once per day when there is something to review.
 */
class ContentReviewRemindersCommand extends Command
{
    public const ACTION = 'content.review_reminder';

    protected $signature = 'rafiq:content:review-reminders {--days=30 : Remind about reviews due within this many days}';

    protected $description = 'Record a reminder for published content whose periodic medical review is due';

    public function handle(AuditLogger $audit): int
    {
        $days = max(1, (int) $this->option('days'));
        $published = ArticleTranslation::query()
            ->where('publish_status', PublishStatus::Published->value)
            ->whereNotNull('review_due_at');

        $overdue = (clone $published)->whereDate('review_due_at', '<', now()->toDateString())->count();
        $dueSoon = (clone $published)
            ->whereDate('review_due_at', '>=', now()->toDateString())
            ->whereDate('review_due_at', '<=', now()->addDays($days)->toDateString())
            ->count();

        $this->line("Overdue: {$overdue}");
        $this->line("Due within {$days} days: {$dueSoon}");

        $alreadyToday = AuditLog::query()
            ->where('action', self::ACTION)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if (($overdue > 0 || $dueSoon > 0) && ! $alreadyToday) {
            $audit->record(self::ACTION, summary: ['overdue' => $overdue, 'due_soon' => $dueSoon, 'window_days' => $days], ip: null);
        }

        return self::SUCCESS;
    }
}
