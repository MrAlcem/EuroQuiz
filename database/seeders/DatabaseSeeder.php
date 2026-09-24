<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'role' => UserRole::User,
            'password' => bcrypt('password'),
        ]);

        User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Admin User',
            'role' => UserRole::Admin,
            'password' => bcrypt('password'),
        ]);

        $this->leaderboardUsers();

        $this->call(QuestionSeeder::class);

        Question::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->pluck('category')
            ->each(static function (string $categoryName): void {
                Category::firstOrCreate(['name' => $categoryName]);
            });
    }

    /**
     * Seed a handful of users with varying total scores so the leaderboard
     * has data to display across all user levels.
     */
    private function leaderboardUsers(): void
    {
        $users = [
            ['name' => 'Lars Jansen', 'email' => 'lars@example.com', 'total_score' => 450],
            ['name' => 'Emma de Vries', 'email' => 'emma@example.com', 'total_score' => 320],
            ['name' => 'Sven Andersson', 'email' => 'sven@example.com', 'total_score' => 210],
            ['name' => 'Ivana Horvat', 'email' => 'ivana@example.com', 'total_score' => 150],
            ['name' => 'Noah Bakker', 'email' => 'noah@example.com', 'total_score' => 90],
            ['name' => 'Klara Nilsson', 'email' => 'klara@example.com', 'total_score' => 40],
            ['name' => 'Marko Kovač', 'email' => 'marko@example.com', 'total_score' => 15],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], [
                'name' => $user['name'],
                'role' => UserRole::User,
                'password' => bcrypt('password'),
                'total_score' => $user['total_score'],
            ]);
        }
    }
}
