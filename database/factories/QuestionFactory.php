<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_text' => fake()->sentence().'?',
            'option_a' => fake()->word(),
            'option_b' => fake()->word(),
            'option_c' => fake()->word(),
            'option_d' => fake()->word(),
            'correct_option' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'category' => fake()->word(),
            'country' => fake()->randomElement(['NL', 'HR']),
            'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
        ];
    }

    /**
     * Indicate that the question has a specific difficulty.
     */
    public function difficulty(string $difficulty): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => $difficulty,
        ]);
    }

    /**
     * Indicate that the question's correct option is a specific value.
     */
    public function correctOption(string $option): static
    {
        return $this->state(fn (array $attributes) => [
            'correct_option' => $option,
        ]);
    }
}
