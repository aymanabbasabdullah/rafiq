<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kinds of Knowledge Center content (Database-Design §3.2).
 */
enum ArticleKind: string
{
    case Article = 'article';
    case Faq = 'faq';
    case PlatformGuide = 'platform_guide';

    /**
     * Medical content must cite at least one source before review (BR-08-1); platform guides explain Rafiq itself.
     */
    public function requiresSource(): bool
    {
        return $this !== self::PlatformGuide;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $kind): string => $kind->value, self::cases());
    }
}
