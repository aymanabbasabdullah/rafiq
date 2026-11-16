<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Services\Content\ArabicNormalizer;

/**
 * SAFE-010: finds wording that diagnoses, falsely reassures, scores fertility or gives dosing,
 * using `resources/clinical/forbidden_phrases.json`. Matching ignores case and Arabic diacritics.
 */
final class ForbiddenPhraseChecker
{
    private ArabicNormalizer $normalizer;

    /** @var array{phrases: array<string, list<string>>, patterns: list<string>} */
    private array $dictionary;

    public function __construct(?string $path = null)
    {
        $this->normalizer = new ArabicNormalizer;
        $decoded = json_decode((string) file_get_contents($path ?? resource_path('clinical/forbidden_phrases.json')), true);

        $this->dictionary = [
            'phrases' => (array) ($decoded['phrases'] ?? []),
            'patterns' => array_values((array) ($decoded['patterns'] ?? [])),
        ];
    }

    /**
     * @return list<string> The forbidden phrases or patterns found in the text.
     */
    public function find(string $text, string $locale): array
    {
        $haystack = mb_strtolower($this->normalizer->normalize($text));
        $found = [];

        foreach ($this->dictionary['phrases'][$locale] ?? [] as $phrase) {
            $needle = mb_strtolower($this->normalizer->normalize($phrase));

            if ($needle !== '' && preg_match('/(?<![\p{L}])'.preg_quote($needle, '/').'(?![\p{L}])/u', $haystack) === 1) {
                $found[] = $phrase;
            }
        }

        foreach ($this->dictionary['patterns'] as $pattern) {
            if (preg_match('/'.$pattern.'/iu', $text) === 1) {
                $found[] = $pattern;
            }
        }

        return $found;
    }
}
