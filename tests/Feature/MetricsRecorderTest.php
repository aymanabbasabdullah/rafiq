<?php

namespace Tests\Feature;

use App\Services\Analytics\MetricsRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class MetricsRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_increment_creates_then_increases_a_daily_counter(): void
    {
        $this->freezeTime();
        $recorder = app(MetricsRecorder::class);

        $recorder->increment('journey_selected', 'learn');
        $recorder->increment('journey_selected', 'learn');
        $recorder->increment('journey_selected', 'concern');

        $this->assertDatabaseHas('daily_metrics', ['date' => now()->toDateString(), 'metric' => 'journey_selected', 'dimension' => 'learn', 'value' => 2]);
        $this->assertDatabaseHas('daily_metrics', ['metric' => 'journey_selected', 'dimension' => 'concern', 'value' => 1]);
        $this->assertDatabaseCount('daily_metrics', 2);
    }

    public function test_counters_are_kept_per_day(): void
    {
        $recorder = app(MetricsRecorder::class);

        $this->travelTo(now()->setDate(2026, 11, 14));
        $recorder->increment('page_view', 'landing');
        $this->travelTo(now()->setDate(2026, 11, 15));
        $recorder->increment('page_view', 'landing');

        $this->assertDatabaseCount('daily_metrics', 2);
    }

    public function test_free_text_is_rejected_as_a_metric_dimension(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(MetricsRecorder::class)->increment('ai_request', 'I have pain in my testicle');
    }

    public function test_viewing_the_landing_page_records_only_an_aggregate_counter(): void
    {
        $this->get('/ar')->assertOk();

        $this->assertDatabaseHas('daily_metrics', ['metric' => 'page_view', 'dimension' => 'landing', 'value' => 1]);
        $this->assertSame(['id', 'date', 'metric', 'dimension', 'value'], Schema::getColumnListing('daily_metrics'));
    }
}
