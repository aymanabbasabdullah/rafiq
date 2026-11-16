<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Analytics\MetricsRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Anonymous report feedback and next-step signals (competition criterion "measurable value"): a trust
 * rating, whether the guidance was clear, and which next step the visitor took. Each is one counter
 * increment from a fixed list — no text, no answers, no result, nothing tied to the visitor.
 */
class ReportFeedbackController extends Controller
{
    /**
     * @var array<string, list<string>>
     */
    public const SIGNALS = [
        'report_trust' => ['1', '2', '3', '4', '5'],
        'report_clear' => ['yes', 'no'],
        'next_step_taken' => ['doctor_prep', 'print', 'download', 'article'],
    ];

    public function __invoke(Request $request, MetricsRecorder $metrics): JsonResponse
    {
        $validated = $request->validate([
            'signal' => ['required', 'string', Rule::in(array_keys(self::SIGNALS))],
            'value' => ['required', 'string', Rule::in(self::SIGNALS[(string) $request->input('signal')] ?? [])],
        ]);

        $metrics->increment((string) $validated['signal'], (string) $validated['value']);

        return response()->json(['status' => 'recorded']);
    }
}
