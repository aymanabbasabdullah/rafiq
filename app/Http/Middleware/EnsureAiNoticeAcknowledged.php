<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * No AI request is sent before the visitor has read the AI notice in this session (FR-022).
 *
 * Must run after EnsureAgeConfirmed. AI endpoints are JSON-only, so a machine-readable code is returned.
 */
class EnsureAiNoticeAcknowledged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('ai_notice_ack') === true) {
            return $next($request);
        }

        return response()->json(['code' => 'ai_notice_required'], 409);
    }
}
