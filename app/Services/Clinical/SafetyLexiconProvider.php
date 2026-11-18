<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Contracts\Safety\SafetyLexicon;
use App\Models\RulesetRelease;
use App\Services\Clinical\Exceptions\NoActiveRuleset;
use App\Services\Content\ArabicNormalizer;
use Illuminate\Database\QueryException;

/**
 * Reads the red-flag lexicon shipped with the active ruleset release. When no release may run, it
 * falls back to the lexicon file of the configured ruleset, so the safety net is never empty.
 */
final class SafetyLexiconProvider implements SafetyLexicon
{
    /** @var array<string, list<string>>|null */
    private ?array $phrases = null;

    public function __construct(private readonly ActiveRulesetResolver $resolver, private readonly ArabicNormalizer $normalizer) {}

    public function phrases(string $locale): array
    {
        return array_values($this->all()[$locale] ?? []);
    }

    public function matches(string $text, string $locale): bool
    {
        $haystack = mb_strtolower($this->normalizer->normalize($text));

        foreach ($this->phrases($locale) as $phrase) {
            if (str_contains($haystack, mb_strtolower($this->normalizer->normalize($phrase)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, list<string>>
     */
    private function all(): array
    {
        if ($this->phrases !== null) {
            return $this->phrases;
        }

        try {
            $version = $this->resolver->resolve()->version;
            $phrases = RulesetRelease::query()->firstWhere('version', $version)?->lexicon;
        } catch (NoActiveRuleset|QueryException) {
            // No release, or the database is unreachable: the safety net must still work from the file.
            $phrases = null;
        }

        if (! is_array($phrases) || $phrases === []) {
            $phrases = RulesetFiles::read(resource_path('clinical/rulesets/'.config('rafiq.demo_ruleset')))['lexicon'];
        }

        return $this->phrases = $phrases;
    }
}
