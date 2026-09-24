<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string>
     */
    private array $translatableColumns = ['question_text', 'option_a', 'option_b', 'option_c', 'option_d'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            foreach ($this->translatableColumns as $column) {
                $table->json("{$column}_tmp")->nullable()->after($column);
            }
        });

        DB::table('questions')->orderBy('id')->chunkById(100, function ($questions) {
            foreach ($questions as $question) {
                DB::table('questions')->where('id', $question->id)->update(
                    collect($this->translatableColumns)->mapWithKeys(
                        fn (string $column) => ["{$column}_tmp" => json_encode(['en' => $question->$column], JSON_UNESCAPED_UNICODE)]
                    )->all()
                );
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn($this->translatableColumns);
        });

        Schema::table('questions', function (Blueprint $table) {
            foreach ($this->translatableColumns as $column) {
                $table->renameColumn("{$column}_tmp", $column);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('question_text_tmp')->nullable()->after('question_text');

            foreach (['option_a', 'option_b', 'option_c', 'option_d'] as $column) {
                $table->string("{$column}_tmp")->nullable()->after($column);
            }
        });

        DB::table('questions')->orderBy('id')->chunkById(100, function ($questions) {
            foreach ($questions as $question) {
                DB::table('questions')->where('id', $question->id)->update(
                    collect($this->translatableColumns)->mapWithKeys(function (string $column) use ($question) {
                        $translations = json_decode($question->$column, true) ?? [];

                        return ["{$column}_tmp" => $translations['en'] ?? reset($translations) ?: ''];
                    })->all()
                );
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn($this->translatableColumns);
        });

        Schema::table('questions', function (Blueprint $table) {
            foreach ($this->translatableColumns as $column) {
                $table->renameColumn("{$column}_tmp", $column);
            }
        });
    }
};
