<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Locale;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
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

        $tasks = $user->can('viewAny', Article::class) ? $this->reviewTasks($user) : [];

        return Inertia::render('Admin/Dashboard', [
            'recoveryCodes' => $request->session()->get('recovery_codes'),
            'tasks' => $tasks,
            'summary' => [
                'pendingReviewsCount' => ArticleTranslation::query()->where('review_status', ReviewStatus::PendingMedicalReview->value)->count(),
                'reviewsDueSoonCount' => ArticleTranslation::query()
                    ->where('publish_status', PublishStatus::Published->value)
                    ->whereNotNull('review_due_at')
                    ->whereDate('review_due_at', '<=', now()->addDays(30))
                    ->count(),
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
     * Work waiting for this admin: translations awaiting medical review (only those this user may review,
     * for verified reviewers) and drafts returned with reviewer comments.
     *
     * @return list<array{id: int, kind: string, title: string, locale: string, url: string}>
     */
    private function reviewTasks(User $user): array
    {
        $pending = ArticleTranslation::query()
            ->where('review_status', ReviewStatus::PendingMedicalReview->value)
            ->orderBy('updated_at')
            ->limit(20)
            ->get()
            ->filter(fn (ArticleTranslation $translation): bool => ! $user->hasVerifiedMedicalReviewerRole()
                || Gate::forUser($user)->allows('four-eyes', [$translation->author_id, $translation->last_editor_id]))
            ->map(fn (ArticleTranslation $translation): array => [
                'id' => $translation->id,
                'kind' => 'review',
                'title' => $translation->title,
                'locale' => $translation->locale,
                'url' => route('admin.content.articles.edit', $translation->article_id),
            ]);

        $returned = ArticleTranslation::query()
            ->where('review_status', ReviewStatus::Draft->value)
            ->whereNotNull('review_comment')
            ->where(fn ($query) => $query->where('author_id', $user->id)->orWhere('last_editor_id', $user->id))
            ->limit(10)
            ->get()
            ->map(fn (ArticleTranslation $translation): array => [
                'id' => $translation->id,
                'kind' => 'returned',
                'title' => $translation->title,
                'locale' => $translation->locale,
                'url' => route('admin.content.articles.edit', $translation->article_id),
            ]);

        return $pending->concat($returned)->take(8)->values()->all();
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
