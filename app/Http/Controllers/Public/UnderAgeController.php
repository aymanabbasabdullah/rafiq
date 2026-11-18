<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Safe page for visitors who say they are under 18 (FR-023, CR-001).
 *
 * It offers no assessment or AI. Its wording is a draft pending medical review (RQ-10).
 */
class UnderAgeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Public/Under18', [
            'ageGateUrl' => route('gate.age'),
        ]);
    }
}
