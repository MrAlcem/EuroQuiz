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
        Schema::table('quiz_sessions', function (Blueprint $table) {
            // Accumulates {question_id, chosen_option, is_correct,
            // points_awarded} per answer() call; flushed into result_answers
            // once the session finishes and its Result is created.
            $table->json('answers_log')->nullable()->after('current_streak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn('answers_log');
        });
    }
};
