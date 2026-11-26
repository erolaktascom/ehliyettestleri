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
            Schema::table($name, fn (Blueprint $table) => $table->timestamp('content_updated_at')->nullable());
            // User-requested baseline for existing published content, not recovered historical evidence.
            $query = DB::table($name)->whereNull('first_published_at');
            $name === 'questions' ? $query->where('status', 'Yayında') : $query->where('is_published', true);
            $query->update(['first_published_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'), 'publication_history_unknown' => false]);
            // Legacy updated_at mixes imports, counters and editorial changes: keep it, but do not
            // infer an editorial date from it. New edits will populate content_updated_at.
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('content_updated_at'));
        }
    }
};
