<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin dashboard home and interface language preference (M11A-FR-06, M11A-FR-07).
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Admin/Dashboard', [
            'recoveryCodes' => $request->session()->get('recovery_codes'),
            'summary' => [
                'pendingReviewsCount' => 0,
                'reviewsDueSoonCount' => 0,
                'activeRuleset' => null,
                'aiEnabled' => (bool) config('rafiq.ai.enabled'),
                'demoMode' => (bool) config('rafiq.demo_mode'),
                'activeAdminsCount' => User::query()->where('is_active', true)->count(),
                'recentAuditCount' => $user->can('viewAny', AuditLog::class)
                    ? AuditLog::query()->where('created_at', '>=', now()->subDay())->count()
                    : null,
            ],
            'recentActivity' => $user->isSuperAdmin() ? $this->recentActivity() : [],
        ]);
    }

    /**
     * Latest administrative actions for the Super Admin overview. Admin activity only — never visitor data.
     *
     * @return list<array{id: int, action: string, actor: string|null, createdAt: string|null}>
     */
    private function recentActivity(): array
    {
        return AuditLog::query()
            ->with('actor:id,name')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->actor?->name,
                'createdAt' => $log->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    public function updateLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(Locale::values())],
        ]);

        $locale = (string) $validated['locale'];
        $user = $request->user();

        if ($user instanceof User) {
            $user->forceFill(['preferred_locale' => $locale])->save();
        }

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return back();
    }
}
