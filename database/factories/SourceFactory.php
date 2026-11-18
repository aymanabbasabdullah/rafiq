<?php

namespace Database\Factories;

use App\Enums\EvidenceType;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => '[SYNTHETIC] '.fake()->sentence(3),
            'publisher' => fake()->company(),
            'url' => 'https://example.org/'.fake()->slug(),
            'publication_date' => '2025',
            'accessed_at' => now()->toDateString(),
            'evidence_type' => EvidenceType::PublicHealthBody,
            'access_note' => null,
            'ai_use_restricted' => false,
        ];
    }
}
