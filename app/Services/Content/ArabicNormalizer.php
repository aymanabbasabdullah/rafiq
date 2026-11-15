<?php

declare(strict_types=1);

namespace App\Services\Content;

/**
 * Normalises text for search so Arabic spelling variants match (Knowledge-Grounding §4).
 *
 * Removes diacritics and tatweel, unifies alef/yeh/teh-marbuta/hamza forms, lowercases
 * Latin text and collapses whitespace. Applied to both stored chunks and queries.
 */
class ArabicNormalizer
{
    private const REPLACEMENTS = [
        'أ' => 'ا',
        'إ' => 'ا',
        'آ' => 'ا',
        'ٱ' => 'ا',
        'ى' => 'ي',
        'ئ' => 'ي',
        'ؤ' => 'و',
        'ة' => 'ه',
    ];

    public function normalize(string $text): string
    {
        $text = (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text);
        $text = strtr($text, self::REPLACEMENTS);
        $text = mb_strtolower($text);
        $text = (string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
