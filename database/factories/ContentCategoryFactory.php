<?php

namespace Database\Factories;

use App\Models\ContentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentCategory>
 */
class ContentCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => Str::slug(fake()->unique()->words(2, true)),
            'icon' => 'book-open',
            'sort_order' => fake()->numberBetween(1, 50),
            'is_visible' => true,
            'ai_enabled' => true,
        ];
    }

    /**
     * Adds Arabic and English names with unique slugs.
     */
    public function translated(): static
    {
        return $this->afterCreating(function (ContentCategory $category): void {
            foreach (['ar' => 'تصنيف تجريبي', 'en' => 'Synthetic topic'] as $locale => $name) {
                $category->translations()->create([
                    'locale' => $locale,
                    'name' => "{$name} {$category->id}",
                    'slug' => "{$locale}-topic-{$category->id}",
                    'description' => $locale === 'ar' ? 'وصف تجريبي.' : 'Synthetic description.',
                ]);
            }
        });
    }
}
