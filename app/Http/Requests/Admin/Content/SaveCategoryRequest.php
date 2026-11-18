<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Enums\Locale;
use App\Models\ContentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A topic with names in both languages. `article` is reserved as a slug (used by /learn/article/{key}).
 */
class SaveCategoryRequest extends FormRequest
{
    public const ICONS = ['book-open', 'heart-pulse', 'info', 'leaf', 'pill', 'sprout', 'triangle-alert'];

    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof ContentCategory
            ? (bool) $this->user()?->can('update', $category)
            : (bool) $this->user()?->can('create', ContentCategory::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $rules = [
            'icon' => ['required', Rule::in(self::ICONS)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_visible' => ['boolean'],
            'ai_enabled' => ['boolean'],
        ];

        if (! $category instanceof ContentCategory) {
            $rules['key'] = ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('content_categories', 'key')];
        }

        foreach (Locale::values() as $locale) {
            $translationId = $category instanceof ContentCategory ? $category->translations()->where('locale', $locale)->value('id') : null;

            $rules["translations.{$locale}.name"] = ['required', 'string', 'max:120'];
            $rules["translations.{$locale}.slug"] = [
                'required', 'string', 'max:120', 'regex:'.SaveArticleRequest::SLUG_PATTERN, Rule::notIn(['article', 'search']),
                Rule::unique('content_category_translations', 'slug')->where('locale', $locale)->ignore($translationId),
            ];
            $rules["translations.{$locale}.description"] = ['nullable', 'string', 'max:300'];
        }

        return $rules;
    }
}
