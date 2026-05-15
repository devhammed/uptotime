<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Monitor;
use App\Enums\MonitorStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => fake()->unique()->url(),
            'check_interval' => fake()->numberBetween(1, 60),
            'threshold' => fake()->numberBetween(1, 100),
            'status' => fake()->randomElement(MonitorStatus::cases()),
        ];
    }
}
