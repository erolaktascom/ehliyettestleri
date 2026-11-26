<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Recover only an explicitly audited date edit matching the last saved
        // revision. Never infer an editor or content change from updated_at alone.
        foreach (['Sayfa' => 'pages', 'Blog Yazısı' => 'blog_posts'] as $type => $table) {
            DB::table('audit_logs')->where('record_type', $type)
                ->where('changed_fields', 'Düzenlendi: Yayın tarihi')->orderByDesc('id')->get()
                ->each(function ($audit) use ($table) {
                    if (!preg_match('/#(\d+)$/', $audit->record_title, $match)) return;
                    $userId = $audit->user_id && DB::table('users')->where('id', $audit->user_id)->exists()
                        ? $audit->user_id : null;
                    DB::table($table)->where('id', $match[1])->where('updated_at', $audit->created_at)
                        ->whereNotNull('first_published_at')->whereNull('content_updated_at')
                        ->whereNull('last_edited_by')->whereNull('last_editor_name')
                        ->update(['content_updated_at' => $audit->created_at,
                            'last_edited_by' => $userId, 'last_editor_name' => $audit->user_name]);
                });
        }
    }

    public function down(): void
    {
        // Preserve verified history and any later edits.
    }
};
