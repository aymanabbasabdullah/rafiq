<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiOutcome;
use App\Models\AiUsageLog;

/**
 * Daily spending cap (AI-013): stops model calls once today's estimated cost reaches the budget.
 * When prices are not configured the cost is unknown, so a daily cap on model calls applies instead.
 */
final class BudgetGuard
{
    public const DAILY_MODEL_CALLS_WITHOUT_PRICING = 300;

    public function __construct(private readonly AiSettings $settings) {}

    public function exceeded(): bool
    {
        $today = AiUsageLog::query()->where('created_at', '>=', now()->startOfDay());
        $budgetMicros = (int) round($this->settings->dailyBudgetUsd() * 1_000_000);

        if ($budgetMicros <= 0) {
            return true;
        }

        $pricingSet = (float) config('rafiq.ai.pricing.input_per_million_usd') > 0 || (float) config('rafiq.ai.pricing.output_per_million_usd') > 0;

        if ($pricingSet) {
            return (int) (clone $today)->sum('estimated_cost_micros') >= $budgetMicros;
        }

        return (clone $today)
            ->whereIn('outcome', [AiOutcome::Answered, AiOutcome::BlockedPostfilter, AiOutcome::RefusedOutOfScope, AiOutcome::RefusedPersonalCase, AiOutcome::Error])
            ->whereNotNull('model')
            ->count() >= self::DAILY_MODEL_CALLS_WITHOUT_PRICING;
    }
}
