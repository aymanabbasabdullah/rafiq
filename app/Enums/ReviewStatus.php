<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Medical review status for rules, questions, guidance texts and content (SAFE-002).
 */
enum ReviewStatus: string
{
    case Draft = 'draft';
    case PendingMedicalReview = 'pending_medical_review';
    case Approved = 'approved';
    case Retired = 'retired';
}
