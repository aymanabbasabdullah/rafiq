<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EvidenceType;
use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A reference an article is checked against (ADM-011). `ai_use_restricted` sources are never fed to the AI.
 *
 * @property int $id
 * @property string $title
 * @property string $publisher
 * @property string $url
 * @property string|null $publication_date
 * @property Carbon|null $accessed_at
 * @property EvidenceType $evidence_type
 * @property string|null $access_note
 * @property bool $ai_use_restricted
 */
#[Fillable(['title', 'publisher', 'url', 'publication_date', 'accessed_at', 'evidence_type', 'access_note', 'ai_use_restricted'])]
class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accessed_at' => 'date',
            'evidence_type' => EvidenceType::class,
            'ai_use_restricted' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Article, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withPivot(['support_level', 'claim_note']);
    }
}
