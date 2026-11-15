<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The four user journeys J1–J4 (FR-010).
 */
enum Journey: string
{
    case Learn = 'learn';
    case Concern = 'concern';
    case Conception = 'conception';
    case DoctorPrep = 'doctor_prep';

    /**
     * Journeys that process temporary health answers require explicit consent (FR-021).
     */
    public function requiresAssessmentConsent(): bool
    {
        return match ($this) {
            self::Concern, self::Conception => true,
            self::Learn, self::DoctorPrep => false,
        };
    }
}
