<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ContentCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Knowledge Center topic (Module-08).
 *
 * @property int $id
 * @property string $key
 * @property string $icon
 * @property int $sort_order
 * @property bool $is_visible
 * @property bool $ai_enabled
 */
#[Fillable(['key', 'icon', 'sort_order', 'is_visible', 'ai_enabled'])]
class ContentCategory extends Model
{
    /** @use HasFactory<ContentCategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
            'ai_enabled' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ContentCategoryTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ContentCategoryTranslation::class);
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function translationFor(string $locale): ?ContentCategoryTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }
}
