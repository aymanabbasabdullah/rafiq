<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiMode;
use App\Services\Ai\Data\AiPrompt;
use App\Services\Ai\Data\GroundingChunk;

/**
 * Assembles the request (AI-Architecture §3 step 6): fixed system instruction, approved chunks inside
 * delimiters marked as data, then the already-filtered message. Nothing identifying is added (GR-06).
 */
final class PromptBuilder
{
    private const PRESETS = [
        'summarize' => 'Summarise the approved content in a few short points.',
        'simplify' => 'Explain the approved content more simply.',
    ];

    /**
     * @param  list<GroundingChunk>  $chunks
     * @param  list<array{role: 'user'|'model', text: string}>  $history
     */
    public function build(AiMode $mode, string $locale, string $message, ?string $preset, array $chunks, array $history, int $simplification, int $maxOutputTokens): AiPrompt
    {
        $context = implode("\n\n", array_map(
            fn (GroundingChunk $chunk): string => "[{$chunk->id}] ".$this->clean($chunk->title.($chunk->heading !== '' ? ' — '.$chunk->heading : ''))."\n".$this->clean($chunk->text),
            $chunks,
        ));

        $request = $preset !== null && isset(self::PRESETS[$preset]) ? self::PRESETS[$preset] : '';

        $userText = "<approved_content>\n{$context}\n</approved_content>\n<mode>{$mode->value}</mode>\n"
            .($request !== '' ? "<request>{$request}</request>\n" : '')
            .'<user_message>'.$this->clean($message).'</user_message>';

        return new AiPrompt(
            systemInstruction: CorePrompt::text($locale, $simplification),
            history: array_map(fn (array $turn): array => ['role' => $turn['role'], 'text' => $this->clean($turn['text'])], $history),
            userText: $userText,
            chunks: $chunks,
            maxOutputTokens: $maxOutputTokens,
        );
    }

    /**
     * Removes anything that could close or fake our delimiters.
     */
    private function clean(string $text): string
    {
        return trim((string) preg_replace('/<\/?\s*(approved_content|user_message|mode|request|system)[^>]*>/iu', '', $text));
    }
}
