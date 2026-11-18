<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Privacy, terms, platform limits and sources pages (FR-024, FR-100).
 *
 * Module-01 provides the structure and summary content; the final privacy
 * policy and terms are completed in Module-03 after legal review (OQ-002).
 */
class LegalPageController extends Controller
{
    /**
     * @var list<string>
     */
    public const PAGES = ['privacy', 'terms', 'limits', 'sources'];

    public function __invoke(string $locale, string $page): Response
    {
        return Inertia::render('Public/Legal', [
            'page' => $page,
            'sources' => $page === 'sources' ? config('rafiq.sources') : [],
            'updatedAt' => (string) config('rafiq.legal_updated_at'),
        ]);
    }
}
