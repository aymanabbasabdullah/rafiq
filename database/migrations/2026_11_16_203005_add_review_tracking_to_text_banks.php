<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Four-eyes review for the doctor question bank and the glossary (M11B step 5): who last edited a text
 * and the reviewer's comment when it is returned. Short constraint names keep MySQL under 64 characters.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const TABLES = [
        'doctor_question_template_translations' => 'doctor_question_tr_editor_fk',
        'glossary_term_translations' => 'glossary_term_tr_editor_fk',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $foreignKey) {
            Schema::table($table, function (Blueprint $blueprint) use ($foreignKey): void {
                $blueprint->unsignedBigInteger('last_editor_id')->nullable();
                $blueprint->text('review_comment')->nullable();
                $blueprint->foreign('last_editor_id', $foreignKey)->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $foreignKey) {
            Schema::table($table, function (Blueprint $blueprint) use ($foreignKey): void {
                $blueprint->dropForeign($foreignKey);
                $blueprint->dropColumn(['last_editor_id', 'review_comment']);
            });
        }
    }
};
