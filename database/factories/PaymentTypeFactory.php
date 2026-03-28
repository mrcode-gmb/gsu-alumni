<?php

namespace Database\Factories;

use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentType>
 */
class PaymentTypeFactory extends Factory
{
    protected $model = PaymentType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'description' => fake()->sentence(),
            'is_active' => true,
            'display_order' => fake()->optional()->numberBetween(1, 10),
        ];
    }
}
