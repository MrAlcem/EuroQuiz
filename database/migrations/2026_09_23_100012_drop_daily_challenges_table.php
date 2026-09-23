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
        // Superseded by per-user, date-seeded selection in
        // QuizQuestionSelector::selectForDate(): each player gets their
        // own daily question set instead of one shared per calendar date.
        Schema::dropIfExists('daily_challenges');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->date('challenge_date')->unique();
            $table->json('question_ids');
            $table->timestamps();
        });
    }
};
