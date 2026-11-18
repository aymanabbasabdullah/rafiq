<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How directly a source supports an article's claims ([مصدر] / [استنتاج] / [مراجعة]).
 */
enum SourceSupport: string
{
    case Direct = 'direct';
    case Inference = 'inference';
    case NeedsReview = 'needs_review';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $support): string => $support->value, self::cases());
    }
}
