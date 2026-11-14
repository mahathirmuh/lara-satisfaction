<?php

namespace Database\Factories\UserManagement;

use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'employee_id' => fake()->unique()->numerify('EMP######'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user may not sign in.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Attach the given role(s) after creation.
     */
    public function withRole(Role|int ...$roles): static
    {
        return $this->afterCreating(function (User $user) use ($roles): void {
            $user->roles()->attach(array_map(
                fn (Role|int $role): int => $role instanceof Role ? $role->getKey() : $role,
                $roles,
            ));
        });
    }
}
