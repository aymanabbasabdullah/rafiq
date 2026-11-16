<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A clinical rule identity (CR-002…). Its logic lives in versions that are immutable once approved.
 *
 * @property int $id
 * @property string $code
 * @property string $title
 */
#[Fillable(['code', 'title'])]
class ClinicalRule extends Model
{
    /**
     * @return HasMany<ClinicalRuleVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ClinicalRuleVersion::class)->orderByDesc('version');
    }

    /**
     * @return HasOne<ClinicalRuleVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(ClinicalRuleVersion::class)->latestOfMany('version');
    }
}
