<?php

namespace Database\Factories\Organization;

use App\Models\Organization\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('??##')),
            'name' => 'PT '.fake()->company(),
            'short_name' => Str::upper(fake()->lexify('???')),
            'location' => fake()->city(),
            'is_active' => true,
        ];
    }
}
