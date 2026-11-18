<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GlossaryTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A bilingual glossary entry (FR-073).
 *
 * @property int $id
 * @property string $key
 */
#[Fillable(['key'])]
class GlossaryTerm extends Model
{
    /** @use HasFactory<GlossaryTermFactory> */
    use HasFactory;

    /**
     * @return HasMany<GlossaryTermTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(GlossaryTermTranslation::class);
    }
}
