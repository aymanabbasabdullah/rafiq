<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Live preview for the editor using the exact renderer the public site uses (M11B-FR-02).
 */
class MarkdownPreviewController extends Controller
{
    public function __invoke(Request $request, MarkdownRenderer $renderer): JsonResponse
    {
        Gate::authorize('create', Article::class);

        $validated = $request->validate(['markdown' => ['present', 'nullable', 'string', 'max:50000']]);

        return response()->json(['html' => $renderer->toHtml((string) ($validated['markdown'] ?? ''))]);
    }
}
