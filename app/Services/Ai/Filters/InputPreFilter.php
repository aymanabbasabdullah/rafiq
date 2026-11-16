<?php

declare(strict_types=1);

namespace App\Services\Ai\Filters;

use App\Services\Content\ArabicNormalizer;

/**
 * Deterministic checks before any model call (AI-Architecture §3 step 4, GR-05/06/07/15).
 *
 * Returns the first reason a message must not reach the model, or null. Personal health details are
 * blocked whole, never partly redacted (GR-07). Patterns are heuristics tuned by the evaluation suites.
 */
final class InputPreFilter
{
    public const INJECTION = 'injection';

    public const PII = 'pii';

    public const FORBIDDEN_REQUEST = 'forbidden_request';

    public const PERSONAL_HEALTH = 'personal_health';

    private const INJECTION_PATTERNS = [
        '/\b(ignore|disregard|forget)\b.{0,20}\b(previous|above|prior|all|your)\b.{0,20}\b(instructions?|rules|prompt)\b/u',
        '/\b(system prompt|developer mode|jailbreak|you are now|act as a doctor|pretend (to be|you are))\b/u',
        '/(تجاهل|انس|انسى)\s*(كل\s*)?(التعليمات|القواعد|الاوامر)/u',
        '/(انت الان|تصرف كطبيب|تظاهر بانك)/u',
    ];

    private const PII_PATTERNS = [
        '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/iu',
        '/(https?:\/\/|www\.)\S+/iu',
        '/\+?\d[\d\s\-().]{6,}\d/u',
        '/\b(my name is|i am called|اسمي)\b/u',
    ];

    private const FORBIDDEN_PATTERNS = [
        '/\b(diagnose|diagnosis for) me\b/u',
        '/\b(do i have|have i got|am i (infertile|sterile))\b/u',
        '/\bis my (result|test|sperm|semen|count|analysis)\b.{0,20}\b(normal|ok|bad|low)\b/u',
        '/\b(what|which) (dose|dosage)\b|\bhow (much|many) (mg|pills|tablets)\b/u',
        '/\b(prescribe|prescription)\b|\b(which|what) (medicine|medication|drug|pill) should i\b|\bshould i (stop|start) taking\b/u',
        '/(هل عندي|هل لدي|هل انا عقيم|هل انا مصاب|شخص حالتي|شخصني)/u',
        '/(هل نتيجتي|هل تحليلي|هل نتايجي).{0,15}(طبيعي|سليم|جيد)/u',
        '/(ما الجرعه|كم الجرعه|كم حبه|اصرف لي|اكتب لي وصفه|اي دواء اخذ|هل اوقف الدواء)/u',
    ];

    private const FIRST_PERSON_EN = '/\b(i have|i\'ve|i feel|i am|i\'m|i noticed|i suffer|my|me)\b/u';

    private const HEALTH_TERMS_EN = '/\b(pain|hurts?|lump|swelling|swollen|erection|sperm|semen|symptoms?|discharge|burning|fever|results?|tests?|testicles?|testis|scrotum|penis|infertile|bleeding|blood)\b/u';

    private const FIRST_PERSON_AR = '/(عندي|لدي|اعاني|اشعر|احس|لاحظت|خصيتي|خصيتاي|نتيجتي|نتايجي|تحليلي|حالتي|زوجتي|انتصابي)/u';

    private const HEALTH_TERMS_AR = '/(الم|وجع|كتله|تورم|انتفاخ|انتصاب|منوي|منويه|اعراض|افرازات|حرقه|حراره|حمي|تحليل|نتيجه|خصيه|خصيتي|عقم|نزيف|دم)/u';

    public function __construct(private readonly ArabicNormalizer $normalizer) {}

    public function check(string $message): ?string
    {
        $text = $this->prepare($message);

        foreach ([self::INJECTION => self::INJECTION_PATTERNS, self::PII => self::PII_PATTERNS, self::FORBIDDEN_REQUEST => self::FORBIDDEN_PATTERNS] as $reason => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text) === 1) {
                    return $reason;
                }
            }
        }

        $englishDisclosure = preg_match(self::FIRST_PERSON_EN, $text) === 1 && preg_match(self::HEALTH_TERMS_EN, $text) === 1;
        $arabicDisclosure = preg_match(self::FIRST_PERSON_AR, $text) === 1 && preg_match(self::HEALTH_TERMS_AR, $text) === 1;

        return $englishDisclosure || $arabicDisclosure ? self::PERSONAL_HEALTH : null;
    }

    /**
     * Lower-case, Arabic letter normalisation and Arabic-Indic digits as Latin digits.
     */
    private function prepare(string $message): string
    {
        $text = strtr($message, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '’' => "'"]);

        return mb_strtolower($this->normalizer->normalize($text));
    }
}
