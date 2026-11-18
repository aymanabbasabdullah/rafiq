<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\ArticleTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArticleTranslation>
 */
class ArticleTranslationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = '[SYNTHETIC] '.fake()->unique()->sentence(4);

        return [
            'article_id' => Article::factory(),
            'locale' => 'en',
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'summary' => 'Synthetic summary for testing.',
            'body_markdown' => "## Overview\n\nSynthetic paragraph about general health education.\n\n## Details\n\nAnother synthetic paragraph.",
            'review_status' => ReviewStatus::Draft,
            'publish_status' => PublishStatus::Draft,
            'version' => 0,
        ];
    }

    public function arabic(): static
    {
        return $this->state(fn (): array => [
            'locale' => 'ar',
            'title' => '[SYNTHETIC] مقال تجريبي '.fake()->unique()->numberBetween(1, 99999),
            'slug' => 'ar-article-'.Str::lower(Str::random(8)),
            'summary' => 'ملخص تجريبي للاختبار.',
            'body_markdown' => "## نظرة عامة\n\nفقرة تجريبية عن الصحة الإنجابية.\n\n## تفاصيل\n\nفقرة تجريبية أخرى.",
        ]);
    }
}
