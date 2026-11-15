<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Http\Controllers\Public\LegalPageController;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\LocalizedUrls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * Only non-personal data is shared: language, direction, interface strings,
     * demo flag and the configured emergency number.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Locale-dependent props are resolved lazily: this runs before the route's
        // SetLocale middleware, so the language is only known when the page renders.
        return [
            ...parent::share($request),
            'locale' => fn (): array => $this->localeProps($request, $this->currentLocale()),
            'translations' => fn (): array => $this->translations($this->currentLocale()),
            'demoMode' => (bool) config('rafiq.demo_mode'),
            'emergency' => [
                'number' => $this->emergencyNumber(),
            ],
            'quickExitUrl' => (string) config('rafiq.quick_exit_url'),
            'links' => fn (): array => $this->links($this->currentLocale()),
            'gates' => fn (): array => [
                'ageConfirmed' => $this->sessionFlag($request, 'age_confirmed'),
                'assessmentConsent' => $this->sessionFlag($request, 'assessment_consent'),
                'aiNoticeAcknowledged' => $this->sessionFlag($request, 'ai_notice_ack'),
            ],
            'admin' => fn (): ?array => $this->adminProps($request),
        ];
    }

    /**
     * Shared admin context for `/admin/*` pages. Null on public pages.
     *
     * @return array<string, mixed>|null
     */
    private function adminProps(Request $request): ?array
    {
        if (! $request->is('admin*')) {
            return null;
        }

        $user = $request->user();

        return [
            'user' => $user instanceof User ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'preferredLocale' => $user->preferred_locale,
                'roles' => $user->activeRoleKeys(),
                'canManageUsers' => $user->can('viewAny', User::class),
                'canViewAuditLog' => $user->can('viewAny', AuditLog::class),
            ] : null,
            'links' => [
                'dashboard' => route('admin.dashboard'),
                'users' => route('admin.users.index'),
                'auditLog' => route('admin.audit.index'),
                'logout' => route('admin.logout'),
                'locale' => route('admin.locale.update'),
            ],
        ];
    }

    /**
     * Named public pages for the current language, so the frontend never hard-codes paths.
     *
     * @return array<string, string>
     */
    private function links(Locale $locale): array
    {
        $links = [
            'home' => route('public.landing', ['locale' => $locale->value]),
            'journeys' => route('journeys.index', ['locale' => $locale->value]),
        ];

        foreach (LegalPageController::PAGES as $page) {
            $links[$page] = route('public.legal', ['locale' => $locale->value, 'page' => $page]);
        }

        $links['ageGate'] = route('gate.age', ['locale' => $locale->value]);
        $links['aiNotice'] = route('gate.ai-notice', ['locale' => $locale->value]);
        $links['clearSession'] = route('session.clear', ['locale' => $locale->value]);

        return $links;
    }

    /**
     * Error pages for unmatched URLs render without a session, so every flag defaults to false there.
     */
    private function sessionFlag(Request $request, string $key): bool
    {
        return $request->hasSession() && $request->session()->get($key) === true;
    }

    private function currentLocale(): Locale
    {
        return Locale::tryFrom(App::getLocale()) ?? Locale::default();
    }

    /**
     * @return array{current: string, direction: string, alternate: array{code: string, name: string, url: string}}
     */
    private function localeProps(Request $request, Locale $locale): array
    {
        return [
            'current' => $locale->value,
            'direction' => $locale->direction(),
            'alternate' => [
                'code' => $locale->alternate()->value,
                'name' => $locale->alternate()->nativeName(),
                'url' => LocalizedUrls::forLocale($request, $locale->alternate()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function translations(Locale $locale): array
    {
        /** @var array<string, string> $lines */
        $lines = app('translator')->getLoader()->load($locale->value, '*', '*');

        return $lines;
    }

    private function emergencyNumber(): ?string
    {
        $country = (string) config('rafiq.emergency.country');
        $number = config("rafiq.emergency.numbers.{$country}");

        return is_string($number) && $number !== '' ? $number : null;
    }
}
