<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->timestamp('first_published_at')->nullable();
            $table->timestamp('content_updated_at')->nullable();
            $table->boolean('publication_history_unknown')->default(false);
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('last_editor_name')->nullable();
        });

        // Preserve recorded publication dates. Generic updated_at also includes
        // system changes and must not be presented as a verified content edit.
        DB::table('pages')->where('status', 'published')->whereNotNull('published_at')
            ->update(['first_published_at' => DB::raw('published_at')]);
        DB::table('pages')->whereNull('first_published_at')
            ->update(['publication_history_unknown' => true]);
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_edited_by');
            $table->dropColumn(['first_published_at', 'content_updated_at', 'publication_history_unknown', 'last_editor_name']);
        });
    }
};
