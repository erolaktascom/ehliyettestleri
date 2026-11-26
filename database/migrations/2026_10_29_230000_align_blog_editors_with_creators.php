<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('blog_posts')->whereNotNull('creator_name')->where('creator_name', '!=', '')
            ->update(['author_name' => DB::raw('creator_name')]);
    }

    public function down(): void
    {
        // Preserve attribution; there is no reliable separate author to restore.
    }
};
