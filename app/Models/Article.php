<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleKind;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A language-neutral Knowledge Center item; its text lives in one translation per locale (Module-08).
 *
 * @property int $id
 * @property int $content_category_id
 * @property string $key
 * @property ArticleKind $kind
 * @property bool $ai_enabled
 * @property int $sort_order
 * @property int|null $created_by
 */
#[Fillable(['content_category_id', 'key', 'kind', 'ai_enabled', 'sort_order', 'created_by'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ArticleKind::class,
            'ai_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ContentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }

    /**
     * @return HasMany<ArticleTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ArticleTranslation::class);
    }

    /**
     * @return BelongsToMany<Source, $this>
     */
    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class)->withPivot(['support_level', 'claim_note'])->orderBy('sources.id');
    }

    public function translationFor(string $locale): ?ArticleTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }
}
