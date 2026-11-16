<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Services\Clinical\Data\AssessmentInput;
use App\Services\Clinical\Data\EvaluationResult;
use App\Services\Clinical\Exceptions\InvalidAssessmentInput;
use JsonException;
use RuntimeException;

/**
 * Runs the clinical test cases (Clinical-Test-Cases.md) against a loaded ruleset and returns a report
 * a medical reviewer can read (M06-FR-07, SAFE-008).
 */
final class RulesetTestRunner
{
    public function __construct(private readonly ClinicalRulesEngine $engine) {}

    /**
     * @return list<array<string, mixed>>
     */
    public static function casesFor(string $version): array
    {
        $path = resource_path("clinical/test-cases/{$version}.json");

        if (! is_file($path)) {
            throw new RuntimeException("No test cases for {$version}.");
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid test case file: {$exception->getMessage()}");
        }

        return array_values((array) ($decoded['cases'] ?? []));
    }

    /**
     * @param  list<array<string, mixed>>  $cases
     * @return array{ruleset: string, source_hash: string, total: int, passed: int, failed: int, results: list<array{id: string, description: string, passed: bool, failures: list<string>, actual: array<string, mixed>}>}
     */
    public function run(LoadedRuleset $ruleset, array $cases): array
    {
        $results = [];

        foreach ($cases as $case) {
            $results[] = $this->runCase($ruleset, $case);
        }

        $passed = count(array_filter($results, fn (array $result): bool => $result['passed']));

        return [
            'ruleset' => $ruleset->version,
            'source_hash' => $ruleset->sourceHash,
            'total' => count($results),
            'passed' => $passed,
            'failed' => count($results) - $passed,
            'results' => $results,
        ];
    }

    /**
     * @param  array<string, mixed>  $case
     * @return array{id: string, description: string, passed: bool, failures: list<string>, actual: array<string, mixed>}
     */
    public function runCase(LoadedRuleset $ruleset, array $case): array
    {
        $input = new AssessmentInput(
            journey: (string) $case['journey'],
            ageConfirmed: (bool) ($case['age_confirmed'] ?? true),
            answers: self::withConventions($ruleset, $case),
        );
        $mode = ($case['mode'] ?? 'evaluate') === 'screen' ? 'screen' : 'evaluate';
        $expected = (array) $case['expected'];
        $failures = [];

        try {
            $result = $this->engine->{$mode}($ruleset, $input);
            $actual = ['http' => 200] + $result->toArray();

            for ($run = 1; $run < (int) ($case['repeat'] ?? 1); $run++) {
                if ($result->toArray() !== $this->engine->{$mode}($ruleset, $input)->toArray()) {
                    $failures[] = "run {$run} differs from the first run (determinism)";

                    break;
                }
            }
        } catch (InvalidAssessmentInput $exception) {
            $result = null;
            $actual = ['http' => 422, 'errors' => $exception->errors];
        }

        $failures = [...$failures, ...$this->compare($expected, $actual, $result)];

        return [
            'id' => (string) $case['id'],
            'description' => (string) ($case['description'] ?? ''),
            'passed' => $failures === [],
            'failures' => $failures,
            'actual' => $actual,
        ];
    }

    /**
     * Fills unlisted safety questions with "no" (or `not_applicable` when hidden), as the case conventions state.
     *
     * @param  array<string, mixed>  $case
     * @return array<string, mixed>
     */
    public static function withConventions(LoadedRuleset $ruleset, array $case): array
    {
        $answers = (array) ($case['answers'] ?? []);
        $omit = (array) ($case['omit'] ?? []);
        $evaluator = new ConditionEvaluator($ruleset->bandOrders());

        foreach ($ruleset->questions as $key => $question) {
            if ($question['section'] !== 'safety' || array_key_exists($key, $answers) || in_array($key, $omit, true)
                || ! in_array($case['journey'], $question['journeys'], true)) {
                continue;
            }

            $visible = $question['show_if'] === null || $evaluator->matches($question['show_if'], $answers);
            $answers[$key] = $visible ? 'no' : ConditionEvaluator::NOT_APPLICABLE;
        }

        return $answers;
    }

    /**
     * @param  array<string, mixed>  $expected
     * @param  array<string, mixed>  $actual
     * @return list<string>
     */
    private function compare(array $expected, array $actual, ?EvaluationResult $result): array
    {
        $failures = [];
        $expectedHttp = (int) ($expected['http'] ?? 200);

        if ($actual['http'] !== $expectedHttp) {
            return ["expected HTTP {$expectedHttp}, got {$actual['http']}"];
        }

        if ($result === null) {
            return [];
        }

        if (array_key_exists('priority', $expected) && $expected['priority'] !== $result->priority?->value) {
            $failures[] = 'priority: expected '.($expected['priority'] ?? 'none').', got '.($result->priority->value ?? 'none');
        }

        if (array_key_exists('stopped', $expected) && $expected['stopped'] !== $result->stopped) {
            $failures[] = 'stopped: expected '.json_encode($expected['stopped']).', got '.json_encode($result->stopped);
        }

        $exact = ['triggered' => $result->triggeredCodes(), 'flags' => $result->flags, 'guidance' => $result->guidanceKeys];

        foreach ($exact as $field => $value) {
            if (array_key_exists($field, $expected) && $expected[$field] !== $value) {
                $failures[] = "{$field}: expected [".implode(', ', $expected[$field]).'], got ['.implode(', ', $value).']';
            }
        }

        $includes = ['flags_include' => $result->flags, 'doctor_topics_include' => $result->doctorTopicKeys];

        foreach ($includes as $field => $value) {
            $missing = array_diff((array) ($expected[$field] ?? []), $value);

            if ($missing !== []) {
                $failures[] = "{$field}: missing [".implode(', ', $missing).']';
            }
        }

        return $failures;
    }
}
