<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Question wording in one language; reviewed per language (CS-9).
 *
 * @property int $id
 * @property int $clinical_question_id
 * @property string $locale
 * @property string $prompt
 * @property string|null $why_we_ask
 * @property array<string, string>|null $option_labels
 * @property ReviewStatus $review_status
 */
#[Fillable(['clinical_question_id', 'locale', 'prompt', 'why_we_ask', 'option_labels', 'review_status'])]
class ClinicalQuestionTranslation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['option_labels' => 'array', 'review_status' => ReviewStatus::class];
    }
}
