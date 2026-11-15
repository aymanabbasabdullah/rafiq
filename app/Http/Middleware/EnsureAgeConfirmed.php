<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\GateRedirects;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires the one-tap 18+ confirmation before any journey, the assessment or the AI (FR-020, ADR-010).
 */
class EnsureAgeConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        $ageConfirmed = $request->session()->get('age_confirmed');

        if ($ageConfirmed === true) {
            return $next($request);
        }

        if ($ageConfirmed === false) {
            return $request->expectsJson()
                ? response()->json(['code' => 'age_ineligible'], 403)
                : redirect()->route('public.under18');
        }

        if ($request->expectsJson()) {
            return response()->json(['code' => 'age_required'], 403);
        }

        $intended = GateRedirects::allowed($request->route()?->getName());

        return redirect()->route('gate.age', $intended !== null ? ['intended' => $intended] : []);
    }
}
