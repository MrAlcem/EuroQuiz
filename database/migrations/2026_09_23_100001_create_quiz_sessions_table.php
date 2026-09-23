<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('question_ids');
            $table->unsignedTinyInteger('current_question_index')->default(0);
            $table->unsignedTinyInteger('lives_remaining')->default(3);
            $table->unsignedInteger('score')->default(0);
            $table->unsignedTinyInteger('correct_answers')->default(0);
            $table->string('status')->default('active');
            $table->string('mode')->default('standard');
            $table->date('daily_date')->nullable();
            $table->timestamp('question_started_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->unique(['user_id', 'daily_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_sessions');
    }
};
