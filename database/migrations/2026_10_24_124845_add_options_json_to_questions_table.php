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
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'options')) {
                $table->json('options')->nullable()->after('media_type');
            }
            if (!Schema::hasColumn('questions', 'option_e')) {
                $table->text('option_e')->nullable()->after('option_d');
            }
            // Make individual option columns nullable so questions can have 2, 3, 4, 5+ options
            $table->text('option_a')->nullable()->change();
            $table->text('option_b')->nullable()->change();
            $table->text('option_c')->nullable()->change();
            $table->text('option_d')->nullable()->change();
            $table->string('correct_option', 5)->default('A')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'options')) {
                $table->dropColumn('options');
            }
            if (Schema::hasColumn('questions', 'option_e')) {
                $table->dropColumn('option_e');
            }
        });
    }
};
