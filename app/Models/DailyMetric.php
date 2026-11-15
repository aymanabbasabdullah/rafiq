<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DailyMetricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An aggregated daily counter (ADR-014).
 *
 * @property Carbon $date
 * @property string $metric
 * @property string $dimension
 * @property int $value
 */
#[Fillable(['date', 'metric', 'dimension', 'value'])]
class DailyMetric extends Model
{
    /** @use HasFactory<DailyMetricFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'value' => 'integer',
        ];
    }
}
