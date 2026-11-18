<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RulesetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A versioned set of clinical rules, questions and guidance texts (e.g. RS-2026.1).
 *
 * @property int $id
 * @property string $version
 * @property RulesetStatus $status
 * @property string $source_hash
 * @property string|null $imported_from_commit
 * @property array<string, list<string>>|null $lexicon
 * @property array<string, mixed>|null $test_report
 * @property Carbon|null $tests_passed_at
 * @property int|null $submitted_by
 * @property Carbon|null $submitted_at
 * @property int|null $medically_approved_by
 * @property Carbon|null $medically_approved_at
 * @property int|null $published_by
 * @property Carbon|null $published_at
 * @property string|null $review_comment
 * @property string|null $notes
 */
#[Fillable(['version', 'status', 'source_hash', 'imported_from_commit', 'lexicon', 'test_report', 'tests_passed_at', 'submitted_by', 'submitted_at', 'medically_approved_by', 'medically_approved_at', 'published_by', 'published_at', 'review_comment', 'notes'])]
class RulesetRelease extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RulesetStatus::class,
            'lexicon' => 'array',
            'test_report' => 'array',
            'tests_passed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'medically_approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<ClinicalRuleVersion, $this>
     */
    public function ruleVersions(): BelongsToMany
    {
        return $this->belongsToMany(ClinicalRuleVersion::class, 'ruleset_release_items');
    }

    /**
     * @return HasMany<ClinicalQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ClinicalQuestion::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<GuidanceText, $this>
     */
    public function guidanceTexts(): HasMany
    {
        return $this->hasMany(GuidanceText::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medically_approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Tests passed for exactly the content currently imported (a re-import invalidates the report).
     */
    public function testsPassed(): bool
    {
        return $this->tests_passed_at !== null && ($this->test_report['source_hash'] ?? null) === $this->source_hash;
    }
}
