<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A safety or assessment question of one release. `definition` holds the full JSON used by the engine.
 *
 * @property int $id
 * @property int $ruleset_release_id
 * @property string $key
 * @property string $section
 * @property string $type
 * @property array<string, mixed> $definition
 * @property int $sort_order
 */
#[Fillable(['ruleset_release_id', 'key', 'section', 'type', 'definition', 'sort_order'])]
class ClinicalQuestion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['definition' => 'array', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<RulesetRelease, $this>
     */
    public function release(): BelongsTo
    {
        return $this->belongsTo(RulesetRelease::class, 'ruleset_release_id');
    }

    /**
     * @return HasMany<ClinicalQuestionTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ClinicalQuestionTranslation::class);
    }
}
