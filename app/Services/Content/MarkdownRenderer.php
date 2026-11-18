<?php

declare(strict_types=1);

namespace App\Services\Content;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Converts editor Markdown into sanitised HTML (ADR-017, SEC-010).
 *
 * Raw HTML is stripped, unsafe links (javascript:, data:, …) are removed and external
 * links open in a new tab without leaking the referrer.
 */
class MarkdownRenderer
{
    private ?MarkdownConverter $converter = null;

    public function toHtml(string $markdown): string
    {
        return trim((string) $this->converter()->convert($markdown));
    }

    /**
     * Plain text with Markdown syntax removed (used for search chunks).
     */
    public function toPlainText(string $markdown): string
    {
        $text = strip_tags($this->toHtml($markdown));

        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5)));
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter !== null) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'external_link' => [
                'internal_hosts' => [(string) parse_url((string) config('app.url'), PHP_URL_HOST)],
                'open_in_new_window' => true,
                'nofollow' => '',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new ExternalLinkExtension);

        return $this->converter = new MarkdownConverter($environment);
    }
}
