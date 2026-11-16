<?php

declare(strict_types=1);

namespace App\Services\Ai\Filters;

use App\Services\Ai\Data\AiResult;
use App\Services\Ai\Data\GroundingChunk;
use App\Services\Content\ArabicNormalizer;

/**
 * Checks the model's answer before anyone sees it (AI-Architecture §3 step 8, GR-01/04/08/19/20).
 * Returns the first failure reason, or null when the answer may be shown. Any failure means the
 * visitor gets the approved fallback reply instead.
 */
final class OutputPostFilter
{
    private const DOSAGE_PATTERNS = [
        '/\d+(\.\d+)?\s?(mg|mcg|ml|iu|ملغ|مغ|مل|ملغم)(?!\p{L})/iu',
        '/\b(once|twice|three times) (a|per) day\b/iu',
        '/(مرتين|ثلاث مرات|مره) (يوميا|في اليوم)/u',
    ];

    private const DIAGNOSIS_PATTERNS = [
        '/\byou (probably |likely |may |might )?(have|suffer from) (an? )?(infection|varicocele|torsion|cancer|infertility|disease|condition|std|sti)\b/iu',
        '/\byou are (infertile|sterile)\b/iu',
        '/\byour (fertility|result|results|sperm count) (is|are) (normal|fine|low|abnormal)\b/iu',
        '/\b(nothing to worry about|don t worry)\b/iu',
        '/(انت مصاب|انك مصاب|لديك (مرض|عدوي|دوالي|عقم|التهاب|سرطان)|انت عقيم|خصوبتك طبيعيه|لا داعي للقلق)/u',
    ];

    public function __construct(private readonly ArabicNormalizer $normalizer) {}

    /**
     * @param  list<GroundingChunk>  $chunks
     */
    public function check(AiResult $result, array $chunks, string $locale, int $maxChars): ?string
    {
        $answer = trim($result->answer);

        if ($answer === '') {
            return 'empty';
        }

        if (mb_strlen($answer) > $maxChars) {
            return 'too_long';
        }

        $allowedIds = array_map(fn (GroundingChunk $chunk): string => $chunk->id, $chunks);

        if ($result->citedChunkIds === [] || array_diff($result->citedChunkIds, $allowedIds) !== []) {
            return 'invalid_citation';
        }

        $normalized = mb_strtolower($this->normalizer->normalize($answer));

        foreach (self::DOSAGE_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return 'dosage';
            }
        }

        foreach (self::DIAGNOSIS_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return 'diagnosis';
            }
        }

        $sourceText = implode("\n", array_map(fn (GroundingChunk $chunk): string => $chunk->text, $chunks));

        if (preg_match_all('/(https?:\/\/|www\.)\S+/iu', $answer, $urls) > 0 && array_filter($urls[0], fn (string $url): bool => ! str_contains($sourceText, $url)) !== []) {
            return 'url';
        }

        if (preg_match_all('/\+?\d[\d\s\-().]{6,}\d/u', $answer, $phones) > 0 && array_filter($phones[0], fn (string $phone): bool => ! str_contains($sourceText, $phone)) !== []) {
            return 'phone';
        }

        return $this->matchesLanguage($answer, $locale) ? null : 'language';
    }

    /**
     * At least half of the letters must belong to the interface language's script (GR-19).
     */
    private function matchesLanguage(string $answer, string $locale): bool
    {
        $arabic = preg_match_all('/\p{Arabic}/u', $answer);
        $latin = preg_match_all('/\p{Latin}/u', $answer);
        $letters = $arabic + $latin;

        if ($letters === 0) {
            return false;
        }

        return ($locale === 'ar' ? $arabic : $latin) / $letters >= 0.5;
    }
}
