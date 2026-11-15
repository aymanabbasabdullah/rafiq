<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Enums\EvidenceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\SaveSourceRequest;
use App\Models\Source;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reference source register (ADM-011).
 */
class SourceController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Source::class);

        return Inertia::render('Admin/Content/Sources', [
            'sources' => Source::query()->withCount('articles')->orderBy('title')->get()->map(fn (Source $source): array => [
                'id' => $source->id,
                'title' => $source->title,
                'publisher' => $source->publisher,
                'url' => $source->url,
                'publication_date' => $source->publication_date,
                'accessed_at' => $source->accessed_at?->toDateString(),
                'evidence_type' => $source->evidence_type->value,
                'access_note' => $source->access_note,
                'ai_use_restricted' => $source->ai_use_restricted,
                'articlesCount' => $source->articles_count,
                'updateUrl' => route('admin.content.sources.update', $source),
            ]),
            'evidenceTypes' => EvidenceType::values(),
            'storeUrl' => route('admin.content.sources.store'),
            'indexUrl' => route('admin.content.index'),
            'canManage' => $request->user()?->can('create', Source::class) ?? false,
        ]);
    }

    public function store(SaveSourceRequest $request): RedirectResponse
    {
        $source = Source::query()->create($this->attributes($request));
        $this->audit->record('source.created', $source, ['title' => $source->title]);

        return back()->with('success', __('admin.content.saved'));
    }

    public function update(SaveSourceRequest $request, Source $source): RedirectResponse
    {
        $source->fill($this->attributes($request));

        if ($source->isDirty()) {
            $changed = array_keys($source->getDirty());
            $source->save();
            $this->audit->record('source.updated', $source, ['fields' => $changed]);
        }

        return back()->with('success', __('admin.content.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(SaveSourceRequest $request): array
    {
        return [...$request->safe()->except('ai_use_restricted'), 'ai_use_restricted' => $request->boolean('ai_use_restricted')];
    }
}
