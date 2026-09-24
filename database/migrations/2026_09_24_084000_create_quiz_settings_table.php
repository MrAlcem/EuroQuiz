<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->unsignedInteger('value');
            $table->timestamps();
        });

        $now = now();
        DB::table('quiz_settings')->insert([
            ['key' => 'quiz_length', 'value' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'lives', 'value' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'timer_seconds', 'value' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'easy_questions', 'value' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'medium_questions', 'value' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'hard_questions', 'value' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'easy_points', 'value' => 15, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'medium_points', 'value' => 25, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'hard_points', 'value' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'streak_length', 'value' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'streak_bonus', 'value' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'daily_bonus', 'value' => 20, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_settings');
    }
};
