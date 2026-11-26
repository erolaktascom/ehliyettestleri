<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'questions', 'topic_categories', 'exams', 'topic_tests',
        'lectures', 'lecture_categories', 'pages', 'blog_posts', 'blog_categories',
        'traffic_signs', 'traffic_sign_categories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                // Keep the attribution readable after account deletion or renaming.
                $table->string('creator_name')->nullable();
            });
        }
        // Historical authorship is not proof of who entered a record. Leave unknown creators null.
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn(['created_by', 'creator_name']);
            });
        }
    }
};
