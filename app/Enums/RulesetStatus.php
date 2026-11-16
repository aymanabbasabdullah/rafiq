<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of a ruleset release (Clinical-Review-Process §3, Database-Design §3.3).
 */
enum RulesetStatus: string
{
    case Draft = 'draft';
    case PendingMedicalReview = 'pending_medical_review';
    case Approved = 'approved';
    case Published = 'published';
    case Superseded = 'superseded';
    case Retired = 'retired';

    /**
     * Imports may only replace a release that has not entered review.
     */
    public function acceptsImport(): bool
    {
        return $this === self::Draft;
    }
}
