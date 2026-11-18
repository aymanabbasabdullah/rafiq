<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMetric;
use App\Services\Analytics\MetricsReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Privacy-safe analytics screen (Module-11-E): aggregated daily counters only, small counts hidden,
 * no raw export and nothing about priorities, red flags or answers.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request, MetricsReport $report): Response
    {
        Gate::authorize('viewAny', DailyMetric::class);

        $period = (int) $request->query('period', '30');
        if (! in_array($period, MetricsReport::PERIODS, true)) {
            $period = 30;
        }

        return Inertia::render('Admin/Analytics/Index', [
            'report' => $report->forPeriod($period, App::getLocale()),
            'periods' => MetricsReport::PERIODS,
            'threshold' => MetricsReport::SUPPRESSION_THRESHOLD,
            'filterUrl' => route('admin.analytics.index'),
        ]);
    }
}
