<?php

namespace Database\Factories\Content;

use App\Models\Content\LandingContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandingContent>
 */
class LandingContentFactory extends Factory
{
    /**
     * Define the model's default state (the original landing page texts).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ...LandingContent::DEFAULTS,
            'hero_image_path' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that a hero image was uploaded to the given public disk path.
     */
    public function withHeroImage(string $path = 'landing/hero.webp'): static
    {
        return $this->state(fn (array $attributes): array => [
            'hero_image_path' => $path,
        ]);
    }
}
