<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Append-only audit log for sensitive admin actions (ADM-004, Database-Design §3.1).
 */
#[Fillable(['actor_id', 'action', 'subject_type', 'subject_id', 'summary', 'ip_hash', 'created_at'])]
class AuditLog extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Audit logs are immutable and cannot be updated.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
