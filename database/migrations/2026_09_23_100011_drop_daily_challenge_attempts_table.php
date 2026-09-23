<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Superseded by per-user daily question selection in
        // QuizQuestionSelector + the quiz_sessions "daily" mode; the
        // once-per-day guard now lives on quiz_sessions instead.
        Schema::dropIfExists('daily_challenge_attempts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('daily_challenge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('challenge_date');
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'challenge_date']);
        });
    }
};
