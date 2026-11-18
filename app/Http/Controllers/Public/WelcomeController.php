<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Analytics\MetricsRecorder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Short welcome screen shown once per session (FR-001).
 */
class WelcomeController extends Controller
{
    public function __invoke(Request $request, MetricsRecorder $metrics): Response
    {
        $request->session()->put('welcomed', true);

        $metrics->increment('page_view', 'welcome');

        return Inertia::render('Public/Welcome', [
            'landingUrl' => route('public.landing'),
            'autoAdvanceSeconds' => (int) config('rafiq.welcome_auto_advance_seconds'),
        ]);
    }
}
