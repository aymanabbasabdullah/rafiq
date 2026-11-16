<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What the visitor asked the AI companion to do (AI-Use-Cases).
 */
enum AiMode: string
{
    case PlatformGuide = 'platform_guide';
    case ExplainArticle = 'explain_article';
    case ExplainTerm = 'explain_term';
    case GeneralEducation = 'general_education';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $mode): string => $mode->value, self::cases());
    }
}
