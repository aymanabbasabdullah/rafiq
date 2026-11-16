<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An approved suggested question (kind "question", grouped by topic) or a "what to bring" item
 * (kind "bring") for the doctor visit list (Module-10). Wording lives in per-language translations.
 *
 * @property int $id
 * @property string $key
 * @property string $kind
 * @property string|null $topic_key
 * @property int $sort_order
 */
#[Fillable(['key', 'kind', 'topic_key', 'sort_order'])]
class DoctorQuestionTemplate extends Model
{
    public const KIND_QUESTION = 'question';

    public const KIND_BRING = 'bring';

    /**
     * @return HasMany<DoctorQuestionTemplateTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(DoctorQuestionTemplateTranslation::class);
    }
}
