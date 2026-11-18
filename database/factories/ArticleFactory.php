<?php

namespace Database\Factories;

use App\Enums\ArticleKind;
use App\Models\Article;
use App\Models\ContentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content_category_id' => ContentCategory::factory()->translated(),
            'key' => Str::slug(fake()->unique()->words(3, true)),
            'kind' => ArticleKind::Article,
            'ai_enabled' => true,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function platformGuide(): static
    {
        return $this->state(['kind' => ArticleKind::PlatformGuide]);
    }
}
