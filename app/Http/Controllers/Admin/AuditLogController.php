<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only audit log viewer with actor/action/date filtering (M11A-FR-05, ADM-004).
 *
 * Super Admins see all entries; verified Medical Reviewers see clinical/content review actions only.
 */
class AuditLogController extends Controller
{
    /**
     * Action prefixes visible to a Medical Reviewer who is not a Super Admin (Security-Architecture §3).
     *
     * @var list<string>
     */
    private const REVIEWER_ACTION_PREFIXES = [
        'article.',
        'glossary.',
        'doctor_question.',
        'clinical.',
        'ruleset.',
        'guidance.',
    ];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        /** @var User $actor */
        $actor = $request->user();

        $query = AuditLog::query()->with('actor:id,name,email')->orderByDesc('id');

        if (! $actor->isSuperAdmin()) {
            $query->where(function ($sub): void {
                foreach (self::REVIEWER_ACTION_PREFIXES as $prefix) {
                    $sub->orWhere('action', 'like', $prefix.'%');
                }
            });
        }

        $actorFilter = $request->query('actor_id');
        if (is_numeric($actorFilter)) {
            $query->where('actor_id', (int) $actorFilter);
        }

        $actionFilter = trim((string) $request->query('action', ''));
        if ($actionFilter !== '') {
            $query->where('action', $actionFilter);
        }

        $fromFilter = trim((string) $request->query('from', ''));
        if ($fromFilter !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromFilter) === 1) {
            $query->where('created_at', '>=', $fromFilter.' 00:00:00');
        }

        $toFilter = trim((string) $request->query('to', ''));
        if ($toFilter !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toFilter) === 1) {
            $query->where('created_at', '<=', $toFilter.' 23:59:59');
        }

        $logs = $query->limit(100)->get()->map(fn (AuditLog $log): array => [
            'id' => $log->id,
            'action' => $log->action,
            'actor' => $log->actor !== null ? [
                'id' => $log->actor->id,
                'name' => $log->actor->name,
                'email' => $log->actor->email,
            ] : null,
            'subjectType' => $log->subject_type !== null ? class_basename($log->subject_type) : null,
            'subjectId' => $log->subject_id,
            'summary' => $log->summary,
            'createdAt' => $log->created_at?->toIso8601String(),
        ]);

        $actors = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u): array => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]);

        return Inertia::render('Admin/AuditLog/Index', [
            'logs' => $logs,
            'actors' => $actors,
            'filters' => [
                'actor_id' => is_numeric($actorFilter) ? (string) $actorFilter : '',
                'action' => $actionFilter,
                'from' => $fromFilter,
                'to' => $toFilter,
            ],
            'filterUrl' => route('admin.audit.index'),
        ]);
    }
}
