<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $content_category_id
 * @property string $locale
 * @property string $name
 * @property string $slug
 * @property string|null $description
 */
#[Fillable(['content_category_id', 'locale', 'name', 'slug', 'description'])]
class ContentCategoryTranslation extends Model
{
    /**
     * @return BelongsTo<ContentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }
}
