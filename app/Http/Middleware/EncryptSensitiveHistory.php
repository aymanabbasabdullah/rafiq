<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Encrypts Inertia page data stored in browser history for sensitive pages (PRIV-008, T-02).
 *
 * Browsers only expose the Web Crypto API in secure contexts (HTTPS or localhost), so on
 * plain-HTTP development hosts encryption is skipped instead of breaking navigation.
 * Production always runs over HTTPS (SEC-001).
 */
class EncryptSensitiveHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        // Always set the flag explicitly: Inertia's response factory is a singleton,
        // so an earlier request in the same process must not leak its setting.
        Inertia::encryptHistory($this->isSecureContext($request));

        return $next($request);
    }

    private function isSecureContext(Request $request): bool
    {
        return $request->isSecure() || in_array($request->getHost(), ['localhost', '127.0.0.1', '[::1]'], true);
    }
}
