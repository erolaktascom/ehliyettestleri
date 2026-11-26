<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seo_settings', fn (Blueprint $table) => $table->dropColumn(['site_title', 'meta_description', 'canonical_base']));
        foreach (['exams', 'topic_tests'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('question_count'));
        }
        Schema::table('notification_campaigns', fn (Blueprint $table) => $table->string('target')->nullable()->default(null)->change());
        Schema::table('automation_rules', fn (Blueprint $table) => $table->string('channels')->nullable()->default(null)->change());
        foreach (DB::table('notification_campaigns')->get() as $row) {
            $groups = json_decode($row->target_groups ?? 'null', true);
            if (!$groups) $groups = \App\Support\NotificationAudience::legacyGroups((string) $row->target);
            if ($groups) DB::table('notification_campaigns')->where('id', $row->id)->update(['target_groups' => json_encode($groups), 'target' => null]);
        }
        foreach (DB::table('automation_rules')->get() as $row) {
            if (json_decode($row->delivery_channels ?? 'null', true)) DB::table('automation_rules')->where('id', $row->id)->update(['channels' => null]);
        }
    }
    public function down(): void
    {
        throw new RuntimeException('Restore the matching code/database backup to recover removed legacy fields.');
    }
};
