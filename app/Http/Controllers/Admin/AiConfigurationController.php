<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AiOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAiConfigurationRequest;
use App\Models\AiConfiguration;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\Ai\AiEvaluator;
use App\Services\Ai\AiSettings;
use App\Services\Ai\BudgetGuard;
use App\Services\Ai\Contracts\AiTextGenerator;
use App\Services\Ai\CorePrompt;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AI configuration (Module-11-D): drafts of safe fields, evaluation, activation, immediate switch-off and a
 * content-free usage panel. The core prompt is read-only (AI-018).
 */
class AiConfigurationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(AiSettings $settings, AiTextGenerator $generator): Response
    {
        Gate::authorize('viewAny', AiConfiguration::class);

        $configurations = AiConfiguration::query()->with('activator')->orderByDesc('version')->get();
        $draft = $configurations->firstWhere('status', AiConfiguration::STATUS_DRAFT);
        $active = $configurations->firstWhere('status', AiConfiguration::STATUS_ACTIVE);

        return Inertia::render('Admin/Ai/Index', [
            'status' => [
                'masterSwitch' => (bool) config('rafiq.ai.enabled'),
                'enabled' => $settings->enabled(),
                'model' => $generator->model(),
                'corePromptVersion' => CorePrompt::VERSION,
                'activeVersion' => $active?->version,
            ],
            'draft' => $draft !== null ? $this->present($draft) : null,
            'active' => $active !== null ? $this->present($active) : null,
            'history' => $configurations->map(fn (AiConfiguration $configuration): array => [
                'version' => $configuration->version,
                'status' => $configuration->status,
                'activatedAt' => $configuration->activated_at?->toDateTimeString(),
                'activatedBy' => $configuration->activator?->name,
            ]),
            'defaults' => $this->defaults(),
            'ceilings' => [
                'dailyBudgetUsd' => (float) config('rafiq.ai.daily_budget_usd'),
                'limits' => config('rafiq.ai.limits'),
            ],
            'usage' => $this->usage($settings),
            'corePrompt' => ['ar' => CorePrompt::text('ar', 2), 'en' => CorePrompt::text('en', 2)],
            'contexts' => AiSettings::SUGGESTION_CONTEXTS,
            'urls' => [
                'createDraft' => route('admin.ai.draft.store'),
                'update' => $draft !== null ? route('admin.ai.draft.update', $draft) : null,
                'evaluate' => $draft !== null ? route('admin.ai.draft.evaluate', $draft) : null,
                'activate' => $draft !== null ? route('admin.ai.draft.activate', $draft) : null,
                'disable' => route('admin.ai.disable'),
            ],
        ]);
    }

    public function storeDraft(Request $request): RedirectResponse
    {
        Gate::authorize('manage', AiConfiguration::class);

        if (AiConfiguration::query()->where('status', AiConfiguration::STATUS_DRAFT)->exists()) {
            return back();
        }

        $active = AiConfiguration::query()->where('status', AiConfiguration::STATUS_ACTIVE)->first();
        $draft = AiConfiguration::query()->create([
            'version' => (int) AiConfiguration::query()->max('version') + 1,
            'status' => AiConfiguration::STATUS_DRAFT,
            'settings' => $active->settings ?? $this->defaults(),
            'core_prompt_version' => CorePrompt::VERSION,
            'created_by' => $request->user()?->id,
        ]);

        $this->audit->record('ai.configuration.draft_created', $draft, ['version' => $draft->version]);

        return back()->with('success', __('admin.ai.done.draft'));
    }

    public function updateDraft(SaveAiConfigurationRequest $request, AiConfiguration $configuration): RedirectResponse
    {
        abort_unless($configuration->status === AiConfiguration::STATUS_DRAFT, 409);

        // Any change invalidates the evaluation report (AI-020).
        $configuration->update(['settings' => $request->settings(), 'eval_report' => null]);
        $this->audit->record('ai.configuration.updated', $configuration, ['version' => $configuration->version]);

        return back()->with('success', __('admin.ai.done.saved'));
    }

    public function evaluate(AiConfiguration $configuration, AiEvaluator $evaluator): RedirectResponse
    {
        Gate::authorize('manage', AiConfiguration::class);
        abort_unless($configuration->status === AiConfiguration::STATUS_DRAFT, 409);

        $report = $evaluator->run('safety');
        $configuration->update(['eval_report' => $report]);
        $this->audit->record('ai.configuration.evaluated', $configuration, ['passed' => $report['passed'], 'total' => $report['total']]);

        return back()->with('success', __('admin.ai.done.evaluated'));
    }

    /**
     * Activation needs a passing evaluation for the current core prompt; it sits behind password confirmation.
     */
    public function activate(Request $request, AiConfiguration $configuration): RedirectResponse
    {
        Gate::authorize('manage', AiConfiguration::class);
        abort_unless($configuration->status === AiConfiguration::STATUS_DRAFT, 409);

        $report = $configuration->eval_report;

        if ($report === null || ($report['failed'] ?? 1) !== 0 || ($report['core_prompt_version'] ?? null) !== CorePrompt::VERSION) {
            return back()->withErrors(['workflow' => __('admin.ai.error.eval_required')]);
        }

        /** @var User $user */
        $user = $request->user();
        $this->makeActive($configuration, $user);

        return back()->with('success', __('admin.ai.done.activated'));
    }

    /**
     * Immediate switch-off (M11D-FR-05): a new active version identical to the current one but disabled.
     * Turning the AI off only reduces risk, so it needs no evaluation.
     */
    public function disable(Request $request): RedirectResponse
    {
        Gate::authorize('manage', AiConfiguration::class);

        /** @var User $user */
        $user = $request->user();
        $active = AiConfiguration::query()->where('status', AiConfiguration::STATUS_ACTIVE)->first();
        $settings = $active->settings ?? $this->defaults();
        $settings['enabled'] = false;

        $configuration = AiConfiguration::query()->create([
            'version' => (int) AiConfiguration::query()->max('version') + 1,
            'status' => AiConfiguration::STATUS_DRAFT,
            'settings' => $settings,
            'core_prompt_version' => CorePrompt::VERSION,
            'created_by' => $user->id,
        ]);

        $this->makeActive($configuration, $user, 'ai.configuration.disabled');

        return back()->with('success', __('admin.ai.done.disabled'));
    }

    private function makeActive(AiConfiguration $configuration, User $user, string $action = 'ai.configuration.activated'): void
    {
        DB::transaction(function () use ($configuration, $user): void {
            AiConfiguration::query()->where('status', AiConfiguration::STATUS_ACTIVE)->update(['status' => AiConfiguration::STATUS_ARCHIVED]);
            $configuration->update(['status' => AiConfiguration::STATUS_ACTIVE, 'activated_by' => $user->id, 'activated_at' => now()]);
        });

        $this->audit->record($action, $configuration, ['version' => $configuration->version, 'enabled' => (bool) ($configuration->settings['enabled'] ?? true)], $user);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(AiConfiguration $configuration): array
    {
        return [
            'id' => $configuration->id,
            'version' => $configuration->version,
            'status' => $configuration->status,
            'settings' => array_replace_recursive($this->defaults(), $configuration->settings),
            'evalReport' => $configuration->eval_report,
            'evalCurrent' => ($configuration->eval_report['core_prompt_version'] ?? null) === CorePrompt::VERSION && ($configuration->eval_report['failed'] ?? 1) === 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        $suggestions = [];

        foreach (AiSettings::SUGGESTION_CONTEXTS as $context) {
            $suggestions[$context] = ['ar' => [], 'en' => []];
        }

        return [
            'enabled' => true,
            'welcome' => ['ar' => null, 'en' => null],
            'suggestions' => $suggestions,
            'default_simplification' => 2,
            'daily_budget_usd' => (float) config('rafiq.ai.daily_budget_usd'),
            'limits' => [],
        ];
    }

    /**
     * Content-free usage summary from ai_usage_logs (M11D-FR-06, AI-017).
     *
     * @return array<string, mixed>
     */
    private function usage(AiSettings $settings): array
    {
        $since = now()->subDays(6)->startOfDay();
        $byOutcome = AiUsageLog::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('outcome, count(*) as total')
            ->groupBy('outcome')
            ->pluck('total', 'outcome');

        $today = AiUsageLog::query()->where('created_at', '>=', now()->startOfDay());

        return [
            'byOutcome' => collect(AiOutcome::cases())->map(fn (AiOutcome $outcome): array => ['outcome' => $outcome->value, 'total' => (int) ($byOutcome[$outcome->value] ?? 0)])->values(),
            'todayRequests' => (clone $today)->count(),
            'todayModelCalls' => (clone $today)->whereNotNull('model')->count(),
            'todayCostUsd' => round((int) (clone $today)->sum('estimated_cost_micros') / 1_000_000, 4),
            'budgetUsd' => $settings->dailyBudgetUsd(),
            'pricingSet' => (float) config('rafiq.ai.pricing.input_per_million_usd') > 0 || (float) config('rafiq.ai.pricing.output_per_million_usd') > 0,
            'callCap' => BudgetGuard::DAILY_MODEL_CALLS_WITHOUT_PRICING,
            'averageLatencyMs' => (int) round((float) AiUsageLog::query()->where('created_at', '>=', $since)->whereNotNull('latency_ms')->avg('latency_ms')),
        ];
    }
}
