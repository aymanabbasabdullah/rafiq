<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Journey;
use App\Http\Controllers\Controller;
use App\Services\Analytics\MetricsRecorder;
use App\Services\Content\KnowledgeCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page: journeys, how it works, the AI's role, a Knowledge Center preview, privacy, limits and FAQs (FR-002..FR-004).
 */
class LandingController extends Controller
{
    public function __invoke(Request $request, MetricsRecorder $metrics, KnowledgeCatalog $catalog): Response
    {
        $request->session()->put('welcomed', true);

        $metrics->increment('page_view', 'landing');
        $metrics->increment('locale_used', App::getLocale());

        $locale = App::getLocale();

        return Inertia::render('Public/Landing', [
            'journeys' => Journey::cards(),
            'knowledge' => Journey::Learn->isEnabled()
                ? ['categories' => $catalog->categories($locale), 'featured' => $catalog->latest($locale, 1)[0] ?? null]
                : null,
        ]);
    }
}
