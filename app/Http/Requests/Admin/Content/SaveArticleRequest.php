<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Enums\ArticleKind;
use App\Enums\Locale;
use App\Enums\SourceSupport;
use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Article metadata, linked sources and the Arabic/English working copies (ADM-010..ADM-012).
 * A language is saved only when its title is filled in; at least one language is required.
 */
class SaveArticleRequest extends FormRequest
{
    public const SLUG_PATTERN = '/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u';

    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article
            ? (bool) $this->user()?->can('update', $article)
            : (bool) $this->user()?->can('create', Article::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $article = $this->route('article');
        $rules = [
            'content_category_id' => ['required', 'integer', Rule::exists('content_categories', 'id')],
            'kind' => ['required', Rule::in(ArticleKind::values())],
            'ai_enabled' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'sources' => ['array'],
            'sources.*.id' => ['required', 'integer', 'distinct', Rule::exists('sources', 'id')],
            'sources.*.support_level' => ['required', Rule::in(SourceSupport::values())],
            'sources.*.claim_note' => ['nullable', 'string', 'max:500'],
            'translations' => ['required', 'array'],
        ];

        if (! $article instanceof Article) {
            $rules['key'] = ['required', 'string', 'max:96', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles', 'key')];
        }

        foreach (Locale::values() as $locale) {
            $translationId = $article instanceof Article ? $article->translations()->where('locale', $locale)->value('id') : null;
            $filled = filled($this->input("translations.{$locale}.title"));
            $required = $filled ? 'required' : 'nullable';

            $rules["translations.{$locale}.title"] = ['nullable', 'string', 'max:200'];
            $rules["translations.{$locale}.slug"] = [
                $required, 'string', 'max:200', 'regex:'.self::SLUG_PATTERN,
                Rule::unique('article_translations', 'slug')->where('locale', $locale)->ignore($translationId),
            ];
            $rules["translations.{$locale}.summary"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$locale}.body_markdown"] = [$required, 'string', 'max:50000'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasAny = collect(Locale::values())->contains(fn (string $locale): bool => filled($this->input("translations.{$locale}.title")));

                if (! $hasAny) {
                    $validator->errors()->add('translations', __('admin.content.error.translation_required'));
                }
            },
        ];
    }

    /**
     * Translations that were filled in, keyed by locale.
     *
     * @return array<string, array{title: string, slug: string, summary: ?string, body_markdown: string}>
     */
    public function filledTranslations(): array
    {
        $result = [];

        foreach (Locale::values() as $locale) {
            $data = $this->validated("translations.{$locale}");

            if (is_array($data) && filled($data['title'] ?? null)) {
                $result[$locale] = [
                    'title' => trim((string) $data['title']),
                    'slug' => (string) $data['slug'],
                    'summary' => filled($data['summary'] ?? null) ? trim((string) $data['summary']) : null,
                    'body_markdown' => (string) $data['body_markdown'],
                ];
            }
        }

        return $result;
    }
}
