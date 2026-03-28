<?php

namespace Database\Factories;

use App\Models\ProgramType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProgramType>
 */
class ProgramTypeFactory extends Factory
{
    protected $model = ProgramType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Program Type '.fake()->unique()->numerify('###');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_active' => true,
            'display_order' => fake()->optional()->numberBetween(1, 10),
        ];
    }
}
