<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Areas in which an answer can show a risk indicator (competition criterion "early and clear risk
 * identification"). Declared per answer option in the ruleset; never stored.
 */
enum RiskDomain: string
{
    case MedicalHistory = 'medical_history';
    case Lifestyle = 'lifestyle';
    case Metabolic = 'metabolic';
    case Infectious = 'infectious';
    case Anatomical = 'anatomical';
    case TestResults = 'test_results';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $domain): string => $domain->value, self::cases());
    }
}
