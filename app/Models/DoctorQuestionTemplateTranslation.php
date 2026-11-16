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
 * Wording of a doctor-visit question or checklist item in one language, reviewed per language (CS-9).
 *
 * @property int $id
 * @property int $doctor_question_template_id
 * @property string $locale
 * @property string $text
 * @property string|null $help
 * @property ReviewStatus $review_status
 * @property PublishStatus $publish_status
 * @property int|null $medically_approved_by
 * @property Carbon|null $medically_approved_at
 */
#[Fillable(['doctor_question_template_id', 'locale', 'text', 'help', 'review_status', 'publish_status', 'medically_approved_by', 'medically_approved_at'])]
class DoctorQuestionTemplateTranslation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class, 'publish_status' => PublishStatus::class, 'medically_approved_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<DoctorQuestionTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DoctorQuestionTemplate::class, 'doctor_question_template_id');
    }
}
