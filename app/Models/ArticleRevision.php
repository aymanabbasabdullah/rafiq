<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Immutable snapshot of a medically approved translation (Database-Design §3.2 `article_revisions`).
 *
 * @property int $id
 * @property int $article_translation_id
 * @property int $version
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string $body_markdown
 * @property string $body_html
 * @property int|null $approved_by
 * @property Carbon $approved_at
 * @property string|null $change_note
 */
#[Fillable(['article_translation_id', 'version', 'title', 'slug', 'summary', 'body_markdown', 'body_html', 'approved_by', 'approved_at', 'change_note'])]
class ArticleRevision extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Approved revisions are immutable; create a new revision instead.');
        });
    }

    /**
     * @return BelongsTo<ArticleTranslation, $this>
     */
    public function translation(): BelongsTo
    {
        return $this->belongsTo(ArticleTranslation::class, 'article_translation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
