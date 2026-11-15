<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Journey;
use App\Http\Controllers\Controller;
use App\Services\Analytics\MetricsRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page: what Rafiq is, what it offers, privacy, the AI's role, limits and the journeys (FR-002..FR-004).
 */
class LandingController extends Controller
{
    public function __invoke(Request $request, MetricsRecorder $metrics): Response
    {
        $request->session()->put('welcomed', true);

        $metrics->increment('page_view', 'landing');
        $metrics->increment('locale_used', App::getLocale());

        return Inertia::render('Public/Landing', [
            'journeys' => Journey::cards(),
        ]);
    }
}
