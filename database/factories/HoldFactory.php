<?php

namespace Database\Factories;

use App\HoldStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hold>
 */
class HoldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'expires_at' => $this->faker->dateTimeBetween('+1 minutes', '+10 minutes'),
            'status' => $this->faker->randomElement([HoldStatus::EXPIRED->value,HoldStatus::ACTIVE->value]),
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
