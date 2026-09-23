<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->foreignId('quiz_session_id')->nullable()->constrained()->nullOnDelete()->after('user_id');
            $table->unsignedInteger('xp_earned')->default(0)->after('score');
            $table->boolean('is_daily')->default(false)->after('xp_earned');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quiz_session_id');
            $table->dropColumn(['xp_earned', 'is_daily']);
        });
    }
};
