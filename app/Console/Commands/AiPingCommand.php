<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\Gemini\GeminiConnectionCheck;
use Illuminate\Console\Command;

/**
 * Makes ONE real request through the assistant's own Gemini generator (generateContent) with a fixed,
 * non-health prompt and reports the result. Never prints the API key or response headers.
 */
class AiPingCommand extends Command
{
    protected $signature = 'rafiq:ai:ping';

    protected $description = 'Check the live connection to Google Gemini through the assistant\'s own provider';

    public function handle(GeminiConnectionCheck $check): int
    {
        $result = $check->run();

        $this->line("Provider: {$result['provider']}");
        $this->line('Model: '.($result['model'] ?: '(not set)'));
        $this->line('HTTP status: '.($result['status'] ?? '(no response)'));

        if ($result['latency_ms'] !== null) {
            $this->line("Latency: {$result['latency_ms']} ms");
        }

        if ($result['text'] !== null) {
            $this->line('Reply: '.$result['text']);
            $this->line('Exact expected reply: '.($result['matches'] ? 'yes' : 'no'));
        }

        if ($result['error'] !== null) {
            $this->error($result['error']);
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
