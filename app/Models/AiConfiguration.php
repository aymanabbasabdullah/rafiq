<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Versioned admin settings for the AI companion (M11-D). Only "safe fields" live here; the core
 * prompt is fixed in code (CorePrompt) and only referenced by version.
 *
 * @property int $id
 * @property int $version
 * @property string $status
 * @property array<string, mixed> $settings
 * @property string $core_prompt_version
 * @property string|null $model
 * @property array<string, mixed>|null $eval_report
 * @property int|null $created_by
 * @property int|null $activated_by
 * @property Carbon|null $activated_at
 */
#[Fillable(['version', 'status', 'settings', 'core_prompt_version', 'model', 'eval_report', 'created_by', 'activated_by', 'activated_at'])]
class AiConfiguration extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['settings' => 'array', 'eval_report' => 'array', 'activated_at' => 'datetime', 'version' => 'integer'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
