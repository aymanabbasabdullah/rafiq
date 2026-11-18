<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A guidance or doctor-topic text referenced by rule outcomes, for example guidance.p0.emergency.
 *
 * @property int $id
 * @property int $ruleset_release_id
 * @property string $key
 * @property string $category
 */
#[Fillable(['ruleset_release_id', 'key', 'category'])]
class GuidanceText extends Model
{
    /**
     * @return HasMany<GuidanceTextTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(GuidanceTextTranslation::class);
    }
}
