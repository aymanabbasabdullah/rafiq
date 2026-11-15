<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Search/grounding fragment of a published revision (ADR-008). Regenerated on every publish.
 *
 * @property int $id
 * @property int $article_translation_id
 * @property int $article_revision_id
 * @property string $locale
 * @property int $chunk_index
 * @property string|null $heading
 * @property string $text
 * @property string $text_normalized
 * @property int $token_estimate
 */
#[Fillable(['article_translation_id', 'article_revision_id', 'locale', 'chunk_index', 'heading', 'text', 'text_normalized', 'token_estimate'])]
class ContentChunk extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<ArticleTranslation, $this>
     */
    public function translation(): BelongsTo
    {
        return $this->belongsTo(ArticleTranslation::class, 'article_translation_id');
    }
}
