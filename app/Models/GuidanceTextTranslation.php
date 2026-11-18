<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Guidance wording in one language, reviewed per language (SAFE-011).
 *
 * @property int $id
 * @property int $guidance_text_id
 * @property string $locale
 * @property string $text_markdown
 * @property ReviewStatus $review_status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 */
#[Fillable(['guidance_text_id', 'locale', 'text_markdown', 'review_status', 'reviewed_by', 'reviewed_at'])]
class GuidanceTextTranslation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class, 'reviewed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<GuidanceText, $this>
     */
    public function guidanceText(): BelongsTo
    {
        return $this->belongsTo(GuidanceText::class);
    }
}
