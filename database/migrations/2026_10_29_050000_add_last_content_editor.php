<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['questions', 'exams', 'topic_tests', 'blog_posts', 'lectures'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('last_editor_name')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('last_edited_by');
                $table->dropColumn('last_editor_name');
            });
        }
    }
};
