<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SessionController extends Controller
{
    /**
     * "Clear everything" (PRIV-011): ends the session, rotates the CSRF token and
     * clears Inertia's browser history so earlier pages cannot be revisited with Back.
     */
    public function clear(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::clearHistory();

        return redirect()->route('public.landing');
    }
}
