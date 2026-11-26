<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->boolean('is_in_app')->default(false);
            // Old sample campaigns must never become real outbound messages.
            $table->boolean('execution_enabled')->default(false)->index();
            $table->timestamp('prepared_at')->nullable();
        });
        Schema::table('automation_rules', function (Blueprint $table) {
            $table->string('trigger')->nullable();
            $table->json('target_groups')->nullable();
            $table->json('delivery_channels')->nullable();
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
        });
        // No actual checks have taken place for the old placeholder rules.
        DB::table('automation_rules')->update(['last_checked_at' => null]);
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('notification_campaigns')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('automation_rules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('dedupe_key', 64)->unique();
            $table->string('event_key');
            $table->string('channel');
            $table->string('status')->default('pending')->index();
            $table->string('subject');
            $table->text('content');
            $table->string('recipient_email')->nullable();
            $table->timestamp('available_at')->index();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'channel', 'status']);
        });
        DB::table('integrations')->insertOrIgnore([
            'integration_key' => 'smtp', 'name' => 'E-posta Gönderimi (SMTP)', 'provider' => 'SMTP',
            'is_active' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::table('notification_campaigns', fn (Blueprint $table) => $table->dropColumn(['is_in_app', 'execution_enabled', 'prepared_at']));
        Schema::table('automation_rules', fn (Blueprint $table) => $table->dropColumn(['trigger', 'target_groups', 'delivery_channels', 'subject', 'content', 'activated_at', 'last_triggered_at']));
        DB::table('integrations')->where('integration_key', 'smtp')->delete();
    }
};
