<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['questions', 'exams', 'topic_tests', 'blog_posts', 'lectures'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->boolean('publication_history_unknown')->default(false));
            // Republishing legacy content must not invent an historical first publication date.
            DB::table($name)->whereNull('first_published_at')->update(['publication_history_unknown' => true]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('publication_history_unknown'));
        }
    }
};
