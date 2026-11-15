<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Journey;
use App\Http\Controllers\Controller;
use App\Services\Analytics\MetricsRecorder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Journey selection page and the entry pages for J1–J4 (FR-010..FR-014, Module-02).
 */
class JourneyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Public/Journeys', [
            'journeys' => Journey::cards(),
            'concernUrl' => Journey::Concern->url(),
        ]);
    }

    public function enter(Request $request, MetricsRecorder $metrics): Response
    {
        $journey = Journey::fromRouteName($request->route()?->getName());

        abort_if($journey === null || ! $journey->isEnabled(), 404);

        $selectedKey = "journeys_selected.{$journey->value}";

        if ($request->session()->get($selectedKey) !== true) {
            $request->session()->put($selectedKey, true);
            $metrics->increment('journey_selected', $journey->value);
        }

        return Inertia::render('Public/JourneyEntry', [
            'journey' => $journey->value,
            'showsRedFlagReminder' => $journey->showsRedFlagReminder(),
            'showsLocalOnlyNotice' => $journey->showsLocalOnlyNotice(),
            'concernUrl' => Journey::Concern->url(),
            'otherJourneys' => Journey::cards(exclude: $journey),
        ]);
    }
}
