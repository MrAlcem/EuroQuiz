<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Result;
use App\Models\ResultAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultAnswer>
 */
class ResultAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'result_id' => Result::factory(),
            'question_id' => Question::factory(),
            'chosen_option' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'is_correct' => fake()->boolean(),
            'points_awarded' => fake()->numberBetween(0, 40),
        ];
    }
}
