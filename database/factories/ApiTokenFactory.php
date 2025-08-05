<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiToken>
 */
class ApiTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true) . ' Token',
            'token' => hash('sha256', \Illuminate\Support\Str::random(64)),
            'abilities' => fake()->randomElements(['read', 'write', 'delete', 'admin'], rand(1, 3)),
            'application_id' => \App\Models\Application::factory(),
            'user_id' => \App\Models\User::factory(),
            'expires_at' => fake()->optional(0.7)->dateTimeBetween('now', '+1 year'), // 70% have expiration
            'last_used_at' => fake()->optional(0.5)->dateTimeBetween('-1 month', 'now'), // 50% have been used
            'is_active' => fake()->boolean(85), // 85% chance of being active
            'created_from_ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate that the token should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the token should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the token should be expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Indicate that the token should never expire.
     */
    public function neverExpires(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Indicate that the token has been recently used.
     */
    public function recentlyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Set specific abilities for the token.
     */
    public function withAbilities(array $abilities): static
    {
        return $this->state(fn (array $attributes) => [
            'abilities' => $abilities,
        ]);
    }
}
