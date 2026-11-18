<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Filters\InputPreFilter;
use App\Services\Ai\Filters\RedFlagInterceptor;
use JsonException;
use RuntimeException;

/**
 * Runs the AI evaluation suites in `resources/ai-eval/*.jsonl` (AI-020, M09-FR-10).
 *
 * The bundled suite checks the deterministic layer (red-flag interception and the pre-filter) and needs
 * no model call, so it runs anywhere. Model-dependent suites (grounding, language) run on staging with a
 * real key and are out of scope here.
 */
final class AiEvaluator
{
    public const SUITES = ['safety'];

    public function __construct(private readonly RedFlagInterceptor $redFlags, private readonly InputPreFilter $preFilter) {}

    /**
     * @return array{suite: string, core_prompt_version: string, total: int, passed: int, failed: int, ran_at: string, failures: list<array{id: string, expected: string, actual: string}>}
     */
    public function run(string $suite): array
    {
        if (! in_array($suite, self::SUITES, true)) {
            throw new RuntimeException("Unknown suite {$suite}.");
        }

        $failures = [];
        $total = 0;

        foreach ($this->cases($suite) as $case) {
            $total++;
            $actual = $this->redFlags->matches($case['message']) ? 'blocked_redflag' : ($this->preFilter->check($case['message']) ?? 'allow');

            if ($actual !== $case['expected']) {
                $failures[] = ['id' => $case['id'], 'expected' => $case['expected'], 'actual' => $actual];
            }
        }

        return [
            'suite' => $suite,
            'core_prompt_version' => CorePrompt::VERSION,
            'total' => $total,
            'passed' => $total - count($failures),
            'failed' => count($failures),
            'ran_at' => now()->toIso8601String(),
            'failures' => $failures,
        ];
    }

    /**
     * @return list<array{id: string, message: string, expected: string}>
     */
    private function cases(string $suite): array
    {
        $cases = [];

        foreach (file(resource_path("ai-eval/{$suite}.jsonl"), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            try {
                $case = json_decode($line, true, 4, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException("Invalid evaluation line: {$exception->getMessage()}");
            }

            $cases[] = ['id' => (string) $case['id'], 'message' => (string) $case['message'], 'expected' => (string) $case['expected']];
        }

        return $cases;
    }
}
