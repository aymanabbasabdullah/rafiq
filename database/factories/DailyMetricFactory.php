<?php

namespace Database\Factories;

use App\Models\DailyMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyMetric>
 */
class DailyMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-30 days')->format('Y-m-d'),
            'metric' => fake()->randomElement(['page_view', 'journey_selected', 'locale_used']),
            'dimension' => fake()->randomElement(['landing', 'learn', 'ar', 'en']),
            'value' => fake()->numberBetween(1, 500),
        ];
    }
}
