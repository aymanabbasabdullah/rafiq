<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Enums\Priority;
use App\Services\Clinical\Data\AssessmentInput;
use App\Services\Clinical\Data\EvaluationResult;
use App\Services\Clinical\Data\TriggeredRule;
use App\Services\Clinical\Exceptions\InvalidAssessmentInput;

/**
 * Deterministic Clinical Rules Engine (Module-06, Clinical-Safety-Framework §5).
 *
 * Independent of the AI (ADR-020): it never calls a model and nothing here depends on language, time
 * or randomness (BR-06-7). Highest priority wins (BR-06-1); the age gate comes first (BR-06-2); a P0
 * rule with `stop` ends evaluation. Invalid input throws instead of guessing (BR-06-4).
 */
final class ClinicalRulesEngine
{
    public const JOURNEYS = ['concern', 'conception'];

    public const UNKNOWN_ANSWERS_TOPIC = 'topic.unknown_answers';

    /**
     * Safety screening only (M04): returns a stopped result for GATE/P0, otherwise the safety findings so far.
     */
    public function screen(LoadedRuleset $ruleset, AssessmentInput $input): EvaluationResult
    {
        return $this->run($ruleset, $input, includeAssessment: false);
    }

    /**
     * Full evaluation (M05/M07): safety and assessment answers together.
     */
    public function evaluate(LoadedRuleset $ruleset, AssessmentInput $input): EvaluationResult
    {
        return $this->run($ruleset, $input, includeAssessment: true);
    }

    private function run(LoadedRuleset $ruleset, AssessmentInput $input, bool $includeAssessment): EvaluationResult
    {
        $evaluator = new ConditionEvaluator($ruleset->bandOrders());
        $context = ['@journey' => $input->journey, '@age_confirmed' => $input->ageConfirmed];

        // Step 3: eligibility gate before anything else, including input validation.
        $gate = $this->matchSection($ruleset, $evaluator, 'gate', $context, $input->journey);

        if ($gate !== []) {
            return $this->result($ruleset, $gate, stopped: true, defaultPriority: null);
        }

        if (! in_array($input->journey, self::JOURNEYS, true)) {
            throw new InvalidAssessmentInput(['_journey' => 'unknown_journey']);
        }

        [$answers, $missingSafety] = $this->normalise($ruleset, $input, $includeAssessment);
        $values = $answers + $context;

        // Step 4: safety rules; P0 with stop returns at once.
        $safety = $this->matchSection($ruleset, $evaluator, 'safety', $values, $input->journey);
        $stops = array_filter($safety, fn (array $match): bool => $match['stop']);

        if ($stops !== []) {
            // An early submission after a P0 answer is accepted (M04-FR-03, TC-36).
            return $this->result($ruleset, $safety, stopped: true, defaultPriority: null);
        }

        if ($missingSafety !== []) {
            throw new InvalidAssessmentInput(array_fill_keys($missingSafety, 'required'));
        }

        if (! $includeAssessment) {
            return $this->result($ruleset, $safety, stopped: false, defaultPriority: null);
        }

        // Step 6: assessment rules for this journey.
        $assessment = $this->matchSection($ruleset, $evaluator, 'assessment', $values, $input->journey);
        $result = $this->result($ruleset, [...$safety, ...$assessment], stopped: false, defaultPriority: Priority::P4);

        return $this->withAnswerFlags($ruleset, $result, $answers, $input->journey);
    }

    /**
     * Validates the answers against the question definitions (§5 step 2) and fills hidden questions with
     * `not_applicable` and skipped assessment questions with `null`.
     *
     * @return array{0: array<string, mixed>, 1: list<string>} Normalised answers and missing visible safety questions.
     */
    private function normalise(LoadedRuleset $ruleset, AssessmentInput $input, bool $includeAssessment): array
    {
        $errors = [];
        $submitted = $input->answers;

        foreach (array_keys($submitted) as $key) {
            $question = $ruleset->questions[$key] ?? null;

            if ($question === null) {
                $errors[(string) $key] = 'unknown_question';
            } elseif (! in_array($input->journey, $question['journeys'], true)) {
                $errors[(string) $key] = 'not_in_journey';
            } elseif (! $includeAssessment && $question['section'] !== 'safety') {
                $errors[(string) $key] = 'not_in_screening';
            }
        }

        if ($errors !== []) {
            throw new InvalidAssessmentInput($errors);
        }

        $evaluator = new ConditionEvaluator($ruleset->bandOrders());
        $answers = [];
        $missing = [];

        foreach ($ruleset->questions as $key => $question) {
            if (! in_array($input->journey, $question['journeys'], true)) {
                continue;
            }

            if (! $includeAssessment && $question['section'] !== 'safety') {
                continue;
            }

            $value = $submitted[$key] ?? null;
            $visible = $question['show_if'] === null || $evaluator->matches($question['show_if'], $submitted);

            if (! $visible) {
                if ($value !== null && $value !== ConditionEvaluator::NOT_APPLICABLE) {
                    $errors[$key] = 'hidden_question_answered';
                }

                $answers[$key] = ConditionEvaluator::NOT_APPLICABLE;

                continue;
            }

            if ($value === null) {
                if ($question['section'] === 'safety') {
                    $missing[] = $key;
                }

                $answers[$key] = null;

                continue;
            }

            $reason = $this->invalidValue($question, $value);

            if ($reason !== null) {
                $errors[$key] = $reason;
            }

            $answers[$key] = $value;
        }

        if ($errors !== []) {
            throw new InvalidAssessmentInput($errors);
        }

        return [$answers, $missing];
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function invalidValue(array $question, mixed $value): ?string
    {
        if ($question['type'] !== 'multi') {
            return is_string($value) && in_array($value, $question['options'], true) ? null : 'invalid_value';
        }

        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            return 'invalid_value';
        }

        foreach ($value as $option) {
            if (! is_string($option) || ! in_array($option, $question['options'], true)) {
                return 'invalid_value';
            }
        }

        if (count(array_unique($value)) !== count($value)) {
            return 'duplicate_value';
        }

        $exclusive = array_intersect($value, $question['exclusive'] ?? []);

        return $exclusive !== [] && count($value) > 1 ? 'exclusive_conflict' : null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<array{code: string, version: int, priority: Priority, stop: bool, flags: list<string>, guidance: list<string>, topics: list<string>, articles: list<string>}>
     */
    private function matchSection(LoadedRuleset $ruleset, ConditionEvaluator $evaluator, string $section, array $values, string $journey): array
    {
        $matches = [];

        foreach ($ruleset->rules as $rule) {
            if ($rule['section'] !== $section || ($rule['enabled'] ?? true) === false || ! in_array($journey, $rule['journeys'], true)) {
                continue;
            }

            $branch = $this->matchingBranch($rule, $evaluator, $values);

            if ($branch === null) {
                continue;
            }

            $outcome = $branch['outcome'];

            $matches[] = [
                'code' => (string) $rule['code'],
                'version' => (int) $rule['version'],
                'priority' => Priority::from((string) ($outcome['priority_by_journey'][$journey] ?? $outcome['priority'])),
                'stop' => (bool) ($outcome['stop'] ?? false),
                'flags' => array_values($outcome['flags'] ?? []),
                'guidance' => array_values($branch['guidance_keys'] ?? $rule['guidance_keys'] ?? []),
                'topics' => array_values($branch['doctor_topic_keys'] ?? $rule['doctor_topic_keys'] ?? []),
                'articles' => array_values($rule['related_article_keys'] ?? []),
            ];
        }

        return $matches;
    }

    /**
     * A rule either has one `when`/`outcome` pair or ordered `branches`; the first matching branch applies.
     *
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>|null
     */
    private function matchingBranch(array $rule, ConditionEvaluator $evaluator, array $values): ?array
    {
        $branches = $rule['branches'] ?? [['when' => $rule['when'], 'outcome' => $rule['outcome']]];

        foreach ($branches as $branch) {
            if ($evaluator->matches($branch['when'], $values)) {
                return $branch;
            }
        }

        return null;
    }

    /**
     * Step 7–8: highest priority wins; guidance ordered by priority then rule code, without duplicates.
     *
     * @param  list<array{code: string, version: int, priority: Priority, stop: bool, flags: list<string>, guidance: list<string>, topics: list<string>, articles: list<string>}>  $matches
     */
    private function result(LoadedRuleset $ruleset, array $matches, bool $stopped, ?Priority $defaultPriority): EvaluationResult
    {
        usort($matches, fn (array $a, array $b): int => ($a['priority']->rank() <=> $b['priority']->rank()) ?: strnatcmp($a['code'], $b['code']));

        $priority = $matches === [] ? $defaultPriority : Priority::highest(...array_column($matches, 'priority'));

        return new EvaluationResult(
            priority: $priority,
            stopped: $stopped,
            triggered: array_map(fn (array $match): TriggeredRule => new TriggeredRule($match['code'], $match['version'], $match['priority']), $matches),
            guidanceKeys: $this->unique(array_merge([], ...array_column($matches, 'guidance'))),
            doctorTopicKeys: $this->unique(array_merge([], ...array_column($matches, 'topics'))),
            relatedArticleKeys: $this->unique(array_merge([], ...array_column($matches, 'articles'))),
            flags: $this->unique(array_merge([], ...array_column($matches, 'flags'))),
            rulesetVersion: $ruleset->version,
            demo: $ruleset->demo,
        );
    }

    /**
     * Adds `unanswered_present` (FR-041) and the "questions I didn't know" doctor topic (§6).
     *
     * @param  array<string, mixed>  $answers
     */
    private function withAnswerFlags(LoadedRuleset $ruleset, EvaluationResult $result, array $answers, string $journey): EvaluationResult
    {
        $flags = $result->flags;
        $topics = $result->doctorTopicKeys;

        foreach ($ruleset->questions as $key => $question) {
            if ($question['section'] !== 'assessment' || ! in_array($journey, $question['journeys'], true)) {
                continue;
            }

            $answer = $answers[$key] ?? null;

            if (! ConditionEvaluator::isAnswered($answer)) {
                $flags[] = 'unanswered_present';

                continue;
            }

            if (array_intersect((array) $answer, $question['unknown_values'] ?? []) !== []) {
                $topics[] = self::UNKNOWN_ANSWERS_TOPIC;
            }
        }

        return new EvaluationResult(
            priority: $result->priority,
            stopped: $result->stopped,
            triggered: $result->triggered,
            guidanceKeys: $result->guidanceKeys,
            doctorTopicKeys: $this->unique($topics),
            relatedArticleKeys: $result->relatedArticleKeys,
            flags: $this->unique($flags),
            rulesetVersion: $result->rulesetVersion,
            demo: $result->demo,
        );
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function unique(array $values): array
    {
        return array_values(array_unique($values));
    }
}
