<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Priority;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One version of a rule's definition (JSON). Once approved its definition can never change (BR-06-5):
 * a change means a new version in Git, imported as Draft.
 *
 * @property int $id
 * @property int $clinical_rule_id
 * @property int $version
 * @property string $section
 * @property array<string, mixed> $definition
 * @property string $definition_hash
 * @property Priority $priority
 * @property string $support
 * @property ReviewStatus $status
 * @property list<array<string, string>> $evidence
 * @property string|null $rationale
 * @property int|null $authored_by
 * @property string|null $imported_from_commit
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_comment
 * @property Carbon|null $retired_at
 */
#[Fillable(['clinical_rule_id', 'version', 'section', 'definition', 'definition_hash', 'priority', 'support', 'status', 'evidence', 'rationale', 'authored_by', 'imported_from_commit', 'reviewed_by', 'reviewed_at', 'review_comment', 'retired_at'])]
class ClinicalRuleVersion extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            if ($version->getOriginal('status') === ReviewStatus::Approved && $version->isDirty(['definition', 'definition_hash', 'priority', 'section'])) {
                throw new LogicException('An approved rule version is immutable; import a new version instead.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'definition' => 'array',
            'priority' => Priority::class,
            'status' => ReviewStatus::class,
            'evidence' => 'array',
            'reviewed_at' => 'datetime',
            'retired_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ClinicalRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(ClinicalRule::class, 'clinical_rule_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsToMany<RulesetRelease, $this>
     */
    public function releases(): BelongsToMany
    {
        return $this->belongsToMany(RulesetRelease::class, 'ruleset_release_items');
    }

    /**
     * Rules can ship disabled until their source is verified (e.g. CR-015b).
     */
    public function isEnabled(): bool
    {
        return ($this->definition['enabled'] ?? true) !== false;
    }
}
