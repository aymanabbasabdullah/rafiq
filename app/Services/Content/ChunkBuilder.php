<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\ArticleRevision;
use App\Models\ArticleTranslation;
use App\Models\ContentChunk;

/**
 * Splits a published revision into search/grounding chunks (ADR-008, Knowledge-Grounding §3).
 *
 * Splits on `##`/`###` headings, then packs paragraphs up to ~TARGET_WORDS words.
 */
class ChunkBuilder
{
    private const TARGET_WORDS = 200;

    public function __construct(
        private readonly MarkdownRenderer $renderer,
        private readonly ArabicNormalizer $normalizer,
    ) {}

    /**
     * Replace the translation's chunks with chunks from the given revision.
     */
    public function rebuild(ArticleTranslation $translation, ArticleRevision $revision): int
    {
        ContentChunk::query()->where('article_translation_id', $translation->id)->delete();

        $index = 0;

        foreach ($this->split($revision) as [$heading, $text]) {
            ContentChunk::query()->create([
                'article_translation_id' => $translation->id,
                'article_revision_id' => $revision->id,
                'locale' => $translation->locale,
                'chunk_index' => $index++,
                'heading' => $heading,
                'text' => $text,
                'text_normalized' => $this->normalizer->normalize(trim(($heading ?? '').' '.$text)),
                'token_estimate' => (int) ceil(mb_strlen($text) / 4),
            ]);
        }

        return $index;
    }

    /**
     * @return list<array{0: string|null, 1: string}>
     */
    public function split(ArticleRevision $revision): array
    {
        $sections = preg_split('/^(?=#{2,3}\s)/m', $revision->body_markdown) ?: [];
        $chunks = [];
        $intro = trim($revision->title.'. '.($revision->summary ?? ''));

        if ($intro !== '.') {
            $chunks[] = [null, $intro];
        }

        foreach ($sections as $section) {
            $section = trim($section);

            if ($section === '') {
                continue;
            }

            $heading = null;

            if (preg_match('/^#{2,3}\s+(.+)$/m', $section, $match) === 1 && str_starts_with($section, '#')) {
                $heading = trim($match[1]);
                $section = trim((string) preg_replace('/^#{2,3}\s+.+$/m', '', $section, 1));
            }

            $buffer = '';

            foreach (preg_split('/\n\s*\n/', $section) ?: [] as $paragraph) {
                $plain = $this->renderer->toPlainText($paragraph);

                if ($plain === '') {
                    continue;
                }

                if ($buffer !== '' && $this->words($buffer.' '.$plain) > self::TARGET_WORDS) {
                    $chunks[] = [$heading, $buffer];
                    $buffer = '';
                }

                $buffer = trim($buffer.' '.$plain);
            }

            if ($buffer !== '') {
                $chunks[] = [$heading, $buffer];
            }
        }

        return $chunks;
    }

    private function words(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text)) ?: []);
    }
}
