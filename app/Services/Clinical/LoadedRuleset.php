<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Services\Clinical\Exceptions\InvalidRuleset;

/**
 * Immutable in-memory ruleset: question definitions and rule definitions of one release.
 *
 * The engine depends only on this class, so the same evaluation runs from the Git files (tests, CLI)
 * or from the imported release in the database (production), and `sourceHash` proves they match.
 */
final readonly class LoadedRuleset
{
    public const SECTION_ORDER = ['gate' => 0, 'safety' => 1, 'assessment' => 2];

    /** @var array<string, array<string, mixed>> */
    public array $questions;

    /** @var list<array<string, mixed>> */
    public array $rules;

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  list<array<string, mixed>>  $rules
     */
    public function __construct(
        public string $version,
        array $questions,
        array $rules,
        public string $sourceHash,
        public bool $demo = false,
    ) {
        $keyed = [];

        foreach ($questions as $question) {
            $keyed[(string) $question['key']] = $question;
        }

        $this->questions = $keyed;

        // Stable order: gate, then safety, then assessment, each in file order.
        $indexed = array_map(null, array_keys($rules), $rules);
        usort($indexed, fn (array $a, array $b): int => [self::SECTION_ORDER[$a[1]['section']] ?? 9, $a[0]] <=> [self::SECTION_ORDER[$b[1]['section']] ?? 9, $b[0]]);
        $this->rules = array_column($indexed, 1);
    }

    /**
     * Load and validate a ruleset from `resources/clinical/rulesets/{version}`.
     */
    public static function fromDirectory(string $directory, bool $demo = false): self
    {
        $files = RulesetFiles::read($directory);

        $errors = (new RulesetSchemaValidator(new ForbiddenPhraseChecker))->validate($files['questions'], $files['rules'], $files['guidance']);

        if ($errors !== []) {
            throw new InvalidRuleset($errors);
        }

        return new self(basename($directory), $files['questions'], $files['rules'], $files['hash'], $demo);
    }

    /**
     * Ordered options of band questions, excluding "prefer not to say"-style values that have no order.
     *
     * @return array<string, list<string>>
     */
    public function bandOrders(): array
    {
        $orders = [];

        foreach ($this->questions as $key => $question) {
            if ($question['type'] === 'band') {
                $orders[$key] = array_values(array_diff($question['options'], $question['unknown_values'] ?? []));
            }
        }

        return $orders;
    }

    public function withDemo(bool $demo): self
    {
        return new self($this->version, array_values($this->questions), $this->rules, $this->sourceHash, $demo);
    }
}
