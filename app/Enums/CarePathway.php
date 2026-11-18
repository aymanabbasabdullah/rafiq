<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where the visitor is guided next (competition criterion "safe routing and referral"). Set per rule outcome
 * in the ruleset, so the destination is clinical data reviewed with the rule, never decided by the AI.
 */
enum CarePathway: string
{
    case Urgent = 'urgent';
    case FertilityServices = 'fertility_services';
    case UrologyAndrology = 'urology_andrology';
    case PrimaryCare = 'primary_care';
    case SelfCare = 'self_care';

    /**
     * Lower rank wins when rules of the same priority point to different destinations.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Urgent => 0,
            self::FertilityServices => 1,
            self::UrologyAndrology => 2,
            self::PrimaryCare => 3,
            self::SelfCare => 4,
        };
    }

    /**
     * Whether a remote (telehealth) consultation can be a reasonable first contact.
     */
    public function suitsTelehealth(): bool
    {
        return in_array($this, [self::PrimaryCare, self::UrologyAndrology, self::FertilityServices], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $pathway): string => $pathway->value, self::cases());
    }
}
