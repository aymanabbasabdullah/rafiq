<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Services\Clinical\Exceptions\InvalidRuleset;
use JsonException;

/**
 * Reads a ruleset directory (questions.json, rules.json, guidance/{ar,en}.json, red_flag_lexicon.json)
 * and computes its source hash over the exact bytes, so tampering between Git and the database is visible.
 */
final class RulesetFiles
{
    public const FILES = ['questions.json', 'rules.json', 'guidance/ar.json', 'guidance/en.json', 'red_flag_lexicon.json'];

    /**
     * @return array{questions: list<array<string, mixed>>, rules: list<array<string, mixed>>, guidance: array<string, array<string, string>>, lexicon: array<string, list<string>>, hash: string}
     */
    public static function read(string $directory): array
    {
        if (! is_dir($directory)) {
            throw new InvalidRuleset(["Ruleset directory not found: {$directory}"]);
        }

        $contents = [];

        foreach (self::FILES as $file) {
            $path = $directory.DIRECTORY_SEPARATOR.$file;

            if (! is_file($path)) {
                throw new InvalidRuleset(["Missing file: {$file}"]);
            }

            $contents[$file] = (string) file_get_contents($path);
        }

        $decoded = [];

        foreach ($contents as $file => $json) {
            try {
                $decoded[$file] = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidRuleset(["{$file}: {$exception->getMessage()}"]);
            }

            if (! is_array($decoded[$file])) {
                throw new InvalidRuleset(["{$file}: expected a JSON object"]);
            }
        }

        $hashInput = '';

        foreach (self::FILES as $file) {
            // Normalise line endings so a Windows checkout hashes like the server's.
            $hashInput .= $file."\n".str_replace("\r\n", "\n", $contents[$file])."\n";
        }

        return [
            'questions' => array_values((array) ($decoded['questions.json']['questions'] ?? [])),
            'rules' => array_values((array) ($decoded['rules.json']['rules'] ?? [])),
            'guidance' => [
                'ar' => (array) ($decoded['guidance/ar.json']['texts'] ?? []),
                'en' => (array) ($decoded['guidance/en.json']['texts'] ?? []),
            ],
            'lexicon' => (array) ($decoded['red_flag_lexicon.json']['phrases'] ?? []),
            'hash' => hash('sha256', $hashInput),
        ];
    }
}
