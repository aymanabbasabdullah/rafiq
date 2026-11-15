<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocalizationFilesTest extends TestCase
{
    public function test_arabic_and_english_interface_strings_have_the_same_keys(): void
    {
        $arabic = $this->translationKeys('ar');
        $english = $this->translationKeys('en');

        $this->assertSame([], array_values(array_diff($arabic, $english)), 'Keys missing from lang/en.json');
        $this->assertSame([], array_values(array_diff($english, $arabic)), 'Keys missing from lang/ar.json');
    }

    public function test_no_interface_string_is_empty(): void
    {
        foreach (['ar', 'en'] as $locale) {
            foreach ($this->translations($locale) as $key => $line) {
                $this->assertNotSame('', trim($line), "Empty translation [{$key}] in lang/{$locale}.json");
            }
        }
    }

    public function test_frontend_uses_logical_css_properties_only(): void
    {
        $physicalClass = '/(?<![\w-])-?(ml|mr|pl|pr|left|right|border-l|border-r|rounded-l|rounded-r|rounded-tl|rounded-tr|rounded-bl|rounded-br|text-left|text-right|float-left|float-right|scroll-ml|scroll-mr|scroll-pl|scroll-pr)-[\w\[\].\/%-]+|(?<![\w-])(text-left|text-right|float-left|float-right)(?![\w-])/';

        $violations = [];

        foreach (File::allFiles(resource_path('js')) as $file) {
            if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
                continue;
            }

            foreach (preg_split('/\R/', $file->getContents()) ?: [] as $number => $line) {
                if (preg_match_all('/className=(?:"([^"]*)"|\{`([^`]*)`\})/', $line, $matches) === 0) {
                    continue;
                }

                foreach ([...$matches[1], ...$matches[2]] as $classes) {
                    if (preg_match($physicalClass, $classes, $found) === 1) {
                        $violations[] = $file->getRelativePathname().':'.($number + 1).' uses '.$found[0];
                    }
                }
            }
        }

        $this->assertSame([], $violations, 'Use logical utilities (ms-/me-/ps-/pe-/start-/end-/text-start/text-end) instead (L10N-005).');
    }

    /**
     * @return array<string, string>
     */
    private function translations(string $locale): array
    {
        $decoded = json_decode(File::get(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @return list<string>
     */
    private function translationKeys(string $locale): array
    {
        $keys = array_keys($this->translations($locale));
        sort($keys);

        return $keys;
    }
}
