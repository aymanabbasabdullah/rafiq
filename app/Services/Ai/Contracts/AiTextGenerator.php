<?php

declare(strict_types=1);

namespace App\Services\Ai\Contracts;

use App\Services\Ai\AiGenerationFailed;
use App\Services\Ai\Data\AiPrompt;
use App\Services\Ai\Data\AiResult;

/**
 * Turns a prompt into a structured answer. Implementations never log prompt or answer text.
 */
interface AiTextGenerator
{
    /**
     * @throws AiGenerationFailed
     */
    public function generate(AiPrompt $prompt): AiResult;

    public function model(): ?string;
}
