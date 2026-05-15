<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorCheck>
 */
class MonitorCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isUp = fake()->boolean();

        $timedOut = fake()->boolean();

        return [
            'monitor_id' => Monitor::factory(),
            'is_up' => $isUp && ! $timedOut,
            'status_code' => $timedOut ? 0 : ($isUp ? fake()->numberBetween(200, 399) : fake()->numberBetween(400, 599)),
            'response_time_ms' => $isUp && ! $timedOut ? fake()->numberBetween(1, 10000) : null,
            'checked_at' => fake()->dateTimeThisMonth(),
        ];
    }
}
