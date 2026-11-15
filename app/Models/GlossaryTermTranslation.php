<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $glossary_term_id
 * @property string $locale
 * @property string $term
 * @property string $definition_markdown
 * @property string $definition_html
 * @property ReviewStatus $review_status
 * @property PublishStatus $publish_status
 * @property int|null $medically_approved_by
 * @property Carbon|null $medically_approved_at
 */
#[Fillable(['glossary_term_id', 'locale', 'term', 'definition_markdown', 'definition_html', 'review_status', 'publish_status', 'medically_approved_by', 'medically_approved_at'])]
class GlossaryTermTranslation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'review_status' => ReviewStatus::class,
            'publish_status' => PublishStatus::class,
            'medically_approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GlossaryTerm, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(GlossaryTerm::class, 'glossary_term_id');
    }
}
