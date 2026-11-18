<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Type of a reference source (Database-Design §3.2).
 */
enum EvidenceType: string
{
    case ClinicalGuideline = 'clinical_guideline';
    case PublicHealthBody = 'public_health_body';
    case PeerReviewed = 'peer_reviewed';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
