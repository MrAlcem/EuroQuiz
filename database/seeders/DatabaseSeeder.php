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
        // User::factory(10)->create();

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

        if (Question::count() === 0) {
            Question::factory(40)->create();
        }

        Question::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->pluck('category')
            ->each(static function (string $categoryName): void {
                Category::firstOrCreate(['name' => $categoryName]);
            });
    }
}
