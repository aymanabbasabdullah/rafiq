<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the security headers required by SEC-001 and SEC-002 to every web response.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Vite::cspNonce() === null) {
            Vite::useCspNonce();
        }

        $response = $next($request);

        // Laravel's local debug error page relies on inline scripts. Debug mode can never
        // be on in production (BootChecks), so this only affects local development.
        $isDebugErrorPage = config('app.debug') && $response->getStatusCode() >= 500;

        if (! $isDebugErrorPage) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy((string) Vite::cspNonce()));
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Self-only policy: no third-party scripts, styles, fonts or connections (PRIV-006).
     *
     * The Vite dev server runs on a separate origin locally, so it is allowed only while hot reloading.
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        $devServer = $this->viteDevServerOrigins();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", ...$devServer['http']],
            'style-src' => ["'self'", "'unsafe-inline'", ...$devServer['http']],
            'font-src' => ["'self'", 'data:', ...$devServer['http']],
            'img-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'", ...$devServer['http'], ...$devServer['ws']],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $directive): string => $directive.' '.implode(' ', $sources))
            ->implode('; ');
    }

    /**
     * @return array{http: list<string>, ws: list<string>}
     */
    private function viteDevServerOrigins(): array
    {
        if (! Vite::isRunningHot()) {
            return ['http' => [], 'ws' => []];
        }

        $hotUrl = rtrim((string) file_get_contents(Vite::hotFile()));
        $parts = parse_url($hotUrl);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return ['http' => [], 'ws' => []];
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $wsOrigin = ($parts['scheme'] === 'https' ? 'wss' : 'ws').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        return ['http' => [$origin], 'ws' => [$wsOrigin]];
    }
}
