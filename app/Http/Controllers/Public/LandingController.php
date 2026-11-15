<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Analytics\MetricsRecorder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page placeholder; the full page is built in Module-01.
 */
class LandingController extends Controller
{
    public function __invoke(MetricsRecorder $metrics): Response
    {
        $metrics->increment('page_view', 'landing');

        return Inertia::render('Public/Landing');
    }
}
