<?php

namespace Database\Factories;

use App\Models\DailyChallengeAttempt;
use App\Models\Result;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<DailyChallengeAttempt>
 */
class DailyChallengeAttemptFactory extends Factory
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
            'challenge_date' => Carbon::today(),
            'result_id' => Result::factory(),
        ];
    }
}
