<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiMode;
use App\Enums\AiOutcome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Metadata about one AI request — never its text, the answer, an IP, a session or a user agent (AI-017).
 *
 * @property int $id
 * @property Carbon $created_at
 * @property AiMode $mode
 * @property string $locale
 * @property AiOutcome $outcome
 * @property int|null $ai_configuration_version
 * @property string $core_prompt_version
 * @property string|null $model
 * @property int|null $input_tokens
 * @property int|null $output_tokens
 * @property int|null $latency_ms
 * @property string|null $error_code
 * @property int|null $estimated_cost_micros
 * @property int|null $article_id
 */
#[Fillable(['created_at', 'mode', 'locale', 'outcome', 'ai_configuration_version', 'core_prompt_version', 'model', 'input_tokens', 'output_tokens', 'latency_ms', 'error_code', 'estimated_cost_micros', 'article_id'])]
class AiUsageLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'mode' => AiMode::class, 'outcome' => AiOutcome::class];
    }
}
