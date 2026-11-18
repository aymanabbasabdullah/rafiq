<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends `/` to the visitor's language: session, then Accept-Language, then the default (L10N-002).
 *
 * The first visit of a session goes to the welcome screen (FR-001); later visits go straight to the landing page.
 */
class RootRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = Locale::tryFrom((string) $request->session()->get('locale'))
            ?? Locale::tryFrom((string) $request->getPreferredLanguage(Locale::values()))
            ?? Locale::default();

        $destination = $request->session()->get('welcomed') === true ? 'public.landing' : 'public.welcome';

        return redirect()->route($destination, ['locale' => $locale->value]);
    }
}
