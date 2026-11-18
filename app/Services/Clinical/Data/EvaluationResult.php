<?php

declare(strict_types=1);

namespace App\Services\Clinical\Data;

use App\Enums\CarePathway;
use App\Enums\Priority;
use App\Enums\RiskDomain;

/**
 * Immutable engine output (M06-FR-06). Never stored (Clinical-Safety-Framework §5 step 9).
 *
 * `priority` is null only for a safety screening that found nothing yet (the assessment decides).
 * `stopped` is true for GATE and for P0 rules with `stop`, which end the flow.
 */
final readonly class EvaluationResult
{
    /**
     * @param  list<TriggeredRule>  $triggered
     * @param  list<string>  $guidanceKeys
     * @param  list<string>  $doctorTopicKeys
     * @param  list<string>  $relatedArticleKeys
     * @param  list<string>  $flags
     * @param  list<CarePathway>  $carePathways  Most appropriate first.
     * @param  list<RiskDomain>  $riskDomains  Areas with at least one risk indicator, in a fixed order.
     */
    public function __construct(
        public ?Priority $priority,
        public bool $stopped,
        public array $triggered,
        public array $guidanceKeys,
        public array $doctorTopicKeys,
        public array $relatedArticleKeys,
        public array $flags,
        public string $rulesetVersion,
        public bool $demo,
        public array $carePathways = [],
        public array $riskDomains = [],
    ) {}

    public function carePathway(): ?CarePathway
    {
        return $this->carePathways[0] ?? null;
    }

    public function safetyPassed(): bool
    {
        return ! $this->stopped;
    }

    /**
     * @return list<string>
     */
    public function triggeredCodes(): array
    {
        return array_map(fn (TriggeredRule $rule): string => $rule->code, $this->triggered);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'priority' => $this->priority?->value,
            'stopped' => $this->stopped,
            'triggered' => array_map(fn (TriggeredRule $rule): array => $rule->toArray(), $this->triggered),
            'guidanceKeys' => $this->guidanceKeys,
            'doctorTopicKeys' => $this->doctorTopicKeys,
            'relatedArticleKeys' => $this->relatedArticleKeys,
            'flags' => $this->flags,
            'rulesetVersion' => $this->rulesetVersion,
            'demo' => $this->demo,
            'carePathways' => array_map(fn (CarePathway $pathway): string => $pathway->value, $this->carePathways),
            'riskDomains' => array_map(fn (RiskDomain $domain): string => $domain->value, $this->riskDomains),
        ];
    }
}
