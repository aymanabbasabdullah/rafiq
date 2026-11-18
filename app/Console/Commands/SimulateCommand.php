<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CarePathway;
use App\Services\Clinical\ActiveRulesetResolver;
use App\Services\Clinical\ClinicalRulesEngine;
use App\Services\Clinical\ConditionEvaluator;
use App\Services\Clinical\Data\AssessmentInput;
use App\Services\Clinical\LoadedRuleset;
use Illuminate\Console\Command;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Runs synthetic visitors through the active ruleset (competition criterion "measurable value through
 * simulation"). Answers are random and reproducible from the seed: the output shows how the routing
 * behaves, not real-world prevalence. Nothing is stored or sent anywhere.
 */
class SimulateCommand extends Command
{
    protected $signature = 'rafiq:simulate {--count=1000 : Number of synthetic visitors} {--seed=2026 : Random seed for a reproducible run}';

    protected $description = 'Simulate synthetic visitors through the clinical rules and summarise routing and risk indicators';

    private const SAFETY_YES_CHANCE = 4;

    private const SKIP_CHANCE = 20;

    private const NEGATIVE_CHANCE = 75;

    /** @var list<string> */
    private const NEGATIVE_OPTIONS = ['none', 'never', 'no', 'not_trying', 'lt_6m', 'lt_35'];

    public function handle(ActiveRulesetResolver $resolver, ClinicalRulesEngine $engine): int
    {
        $ruleset = $resolver->resolve();
        $random = new Randomizer(new Mt19937((int) $this->option('seed')));
        $count = max(1, (int) $this->option('count'));
        $pathways = array_fill_keys(CarePathway::values(), 0);
        $domains = [];
        $remoteOrHome = 0;
        $withIndicators = 0;

        for ($i = 0; $i < $count; $i++) {
            $journey = ClinicalRulesEngine::JOURNEYS[$random->getInt(0, 1)];
            $result = $engine->evaluate($ruleset, new AssessmentInput($journey, true, $this->answers($ruleset, $journey, $random)));
            $pathway = $result->carePathway() ?? CarePathway::SelfCare;
            $pathways[$pathway->value]++;

            if ($pathway === CarePathway::SelfCare || ($pathway->suitsTelehealth() && ! in_array('prompt_in_person_exam', $result->flags, true))) {
                $remoteOrHome++;
            }

            if ($result->riskDomains !== []) {
                $withIndicators++;
            }

            foreach ($result->riskDomains as $domain) {
                $domains[$domain->value] = ($domains[$domain->value] ?? 0) + 1;
            }
        }

        $percent = fn (int $value): string => number_format($value / $count * 100, 1).'%';

        $this->info("Simulated {$count} synthetic visitors on {$ruleset->version} (seed {$this->option('seed')}).");
        $this->table(['Care pathway', 'Visitors', 'Share'], array_map(fn (string $key, int $value): array => [$key, $value, $percent($value)], array_keys($pathways), $pathways));
        arsort($domains);
        $this->table(['Risk domain', 'Visitors', 'Share'], array_map(fn (string $key, int $value): array => [$key, $value, $percent($value)], array_keys($domains), $domains));
        $this->line('With at least one risk indicator: '.$percent($withIndicators));
        $this->line('Can start remotely or at home (no hospital visit first): '.$percent($remoteOrHome));
        $this->line('Synthetic random answers: this shows routing behaviour, not real-world prevalence.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function answers(LoadedRuleset $ruleset, string $journey, Randomizer $random): array
    {
        $evaluator = new ConditionEvaluator($ruleset->bandOrders());
        $answers = [];

        foreach ($ruleset->questions as $key => $question) {
            if (! in_array($journey, $question['journeys'], true)) {
                continue;
            }

            if ($question['show_if'] !== null && ! $evaluator->matches($question['show_if'], $answers)) {
                $answers[$key] = ConditionEvaluator::NOT_APPLICABLE;

                continue;
            }

            if ($question['section'] === 'safety') {
                $answers[$key] = $random->getInt(1, 100) <= self::SAFETY_YES_CHANCE ? 'yes' : 'no';

                continue;
            }

            if ($random->getInt(1, 100) <= self::SKIP_CHANCE) {
                continue;
            }

            /** @var list<string> $options */
            $options = $question['options'];
            $negative = array_values(array_intersect(self::NEGATIVE_OPTIONS, $options));
            // Most people have no risk factor in any one area, so a "none"-type answer is the most common.
            $choice = $negative !== [] && $random->getInt(1, 100) <= self::NEGATIVE_CHANCE
                ? $negative[0]
                : $options[$random->getInt(0, count($options) - 1)];
            $answers[$key] = $question['type'] === 'multi' ? [$choice] : $choice;
        }

        return $answers;
    }
}
