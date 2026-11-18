<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\DoctorQuestionTemplateTranslation;
use App\Models\GlossaryTermTranslation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Gate;

/**
 * Review workflow for short medical texts that have no revision history: the doctor question bank and
 * the glossary (SAFE-002, M11B step 5). Edit → submit → approve or return (four-eyes) → publish or hide.
 *
 * Editing an approved text sends it back to draft, so outside demo mode only reviewed wording is ever
 * shown. Nothing here can approve automatically.
 */
final class TextBankWorkflow
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, string|null>  $fields
     */
    public function update(DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text, User $editor, array $fields): void
    {
        $this->assertNotPending($text);

        $text->forceFill([
            ...$fields,
            'review_status' => ReviewStatus::Draft,
            'medically_approved_by' => null,
            'medically_approved_at' => null,
            'last_editor_id' => $editor->id,
        ])->save();

        $this->record('updated', $text, $editor);
    }

    public function submit(DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text, User $editor): void
    {
        if ($text->review_status !== ReviewStatus::Draft) {
            throw new ContentWorkflowException('admin.bank.error.not_draft');
        }

        $text->forceFill(['review_status' => ReviewStatus::PendingMedicalReview, 'review_comment' => null])->save();
        $this->record('submitted', $text, $editor);
    }

    public function approve(DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text, User $reviewer): void
    {
        $this->assertPending($text);
        $this->assertFourEyes($text, $reviewer);

        $text->forceFill([
            'review_status' => ReviewStatus::Approved,
            'medically_approved_by' => $reviewer->id,
            'medically_approved_at' => now(),
            'review_comment' => null,
        ])->save();

        $this->record('approved', $text, $reviewer);
    }

    public function reject(DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text, User $reviewer, string $comment): void
    {
        $this->assertPending($text);
        $this->assertFourEyes($text, $reviewer);

        $text->forceFill(['review_status' => ReviewStatus::Draft, 'review_comment' => $comment])->save();
        $this->record('rejected', $text, $reviewer);
    }

    public function publish(DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text, User $editor): void
    {
        if ($text->review_status !== ReviewStatus::Approved) {
            throw new ContentWorkflowException('admin.bank.error.not_approved');
        }

        $text->forceFill(['publish_status' => PublishStatus::Published])->save();
        $this->record('published', $text, $editor);
    }

    public function hide(DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text, User $editor): void
    {
        $text->forceFill(['publish_status' => PublishStatus::Hidden])->save();
        $this->record('hidden', $text, $editor);
    }

    private function assertPending(DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text): void
    {
        if ($text->review_status !== ReviewStatus::PendingMedicalReview) {
            throw new ContentWorkflowException('admin.bank.error.not_pending');
        }
    }

    private function assertNotPending(DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text): void
    {
        if ($text->review_status === ReviewStatus::PendingMedicalReview) {
            throw new ContentWorkflowException('admin.bank.error.pending_locked');
        }
    }

    private function assertFourEyes(DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text, User $reviewer): void
    {
        if (! Gate::forUser($reviewer)->allows('four-eyes', [null, $text->last_editor_id])) {
            throw new ContentWorkflowException('admin.content.error.four_eyes');
        }
    }

    private function record(string $event, DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text, User $actor): void
    {
        $prefix = $text instanceof GlossaryTermTranslation ? 'glossary' : 'doctor_question';

        $this->audit->record("{$prefix}.{$event}", $text, ['locale' => $text->locale], $actor);
    }
}
