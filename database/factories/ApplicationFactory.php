<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' ' . fake()->randomElement(['API', 'App', 'Service', 'Platform']),
            'description' => fake()->sentence(10),
            'client_id' => fake()->uuid(),
            'client_secret' => \Illuminate\Support\Str::random(64),
            'callback_urls' => [
                fake()->url() . '/callback',
                fake()->url() . '/auth/callback'
            ],
            'allowed_scopes' => fake()->randomElements(['read', 'write', 'delete', 'admin'], rand(1, 3)),
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'user_id' => \App\Models\User::factory(),
            'rate_limit' => fake()->randomElement([1000, 2000, 5000, 10000]),
        ];
    }

    /**
     * Indicate that the application should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the application should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific scopes for the application.
     */
    public function withScopes(array $scopes): static
    {
        return $this->state(fn (array $attributes) => [
            'allowed_scopes' => $scopes,
        ]);
    }

    /**
     * Set specific rate limit for the application.
     */
    public function withRateLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_limit' => $limit,
        ]);
    }
}
