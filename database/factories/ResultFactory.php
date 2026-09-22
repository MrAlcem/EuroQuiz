<?php

namespace Database\Factories;

use App\Models\Result;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
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
            'score' => fake()->numberBetween(0, 200),
            'correct_answers' => fake()->numberBetween(0, 10),
            'lives_remaining' => fake()->numberBetween(0, 3),
        ];
    }
}
