<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. question_topics
        if (!Schema::hasTable('question_topics')) {
            Schema::create('question_topics', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_animated')->default(false);
                $table->timestamps();
            });
        }

        // 2. question_imports
        if (!Schema::hasTable('question_imports')) {
            Schema::create('question_imports', function (Blueprint $table) {
                $table->id();
                $table->string('file_name');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('completed');
                $table->integer('total')->default(0);
                $table->integer('ready')->default(0);
                $table->integer('failed')->default(0);
                $table->integer('duplicate')->default(0);
                $table->integer('imported')->default(0);
                $table->timestamps();
            });
        }

        // 3. content_categories
        if (!Schema::hasTable('content_categories')) {
            Schema::create('content_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. topics (Ders Konuları)
        if (!Schema::hasTable('topics')) {
            Schema::create('topics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('content_category_id')->nullable()->constrained('content_categories')->nullOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('short_description')->nullable();
                $table->longText('content')->nullable();
                $table->string('icon')->nullable();
                $table->string('status')->default('published');
                $table->timestamp('published_at')->nullable();
                $table->integer('view_count')->default(0);
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->timestamps();
            });
        }

        // 5. pages
        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('short_description')->nullable();
                $table->longText('content')->nullable();
                $table->string('status')->default('published');
                $table->timestamp('published_at')->nullable();
                $table->integer('view_count')->default(0);
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->timestamps();
            });
        }

        // 6. notification_campaigns
        if (!Schema::hasTable('notification_campaigns')) {
            Schema::create('notification_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('type')->default('Genel Duyuru');
                $table->string('target')->default('Tüm Üyeler');
                $table->boolean('is_email')->default(false);
                $table->boolean('is_push')->default(true);
                $table->string('status')->default('Gönderildi');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->integer('recipient_count')->default(0);
                $table->text('content')->nullable();
                $table->timestamps();
            });
        }

        // 7. automation_rules
        if (!Schema::hasTable('automation_rules')) {
            Schema::create('automation_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->string('channels')->default('E-posta, Push');
                $table->integer('wait_hours')->default(24);
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamps();
            });
        }

        // 8. seo_not_found_logs
        if (!Schema::hasTable('seo_not_found_logs')) {
            Schema::create('seo_not_found_logs', function (Blueprint $table) {
                $table->id();
                $table->string('url');
                $table->integer('request_count')->default(1);
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }

        // 9. seo_redirects
        if (!Schema::hasTable('seo_redirects')) {
            Schema::create('seo_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('old_url');
                $table->string('new_url');
                $table->string('type')->default('301');
                $table->string('status')->default('Aktif');
                $table->integer('usage_count')->default(0);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        // 10. seo_settings
        if (!Schema::hasTable('seo_settings')) {
            Schema::create('seo_settings', function (Blueprint $table) {
                $table->id();
                $table->string('site_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->text('keywords')->nullable();
                $table->string('canonical_base')->nullable();
                $table->text('robots_txt')->nullable();
                $table->boolean('sitemap_enabled')->default(true);
                $table->timestamps();
            });
        }

        // 11. integrations
        if (!Schema::hasTable('integrations')) {
            Schema::create('integrations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('provider');
                $table->boolean('is_active')->default(false);
                $table->text('settings')->nullable();
                $table->timestamps();
            });
        }

        // 12. advertisements
        if (!Schema::hasTable('advertisements')) {
            Schema::create('advertisements', function (Blueprint $table) {
                $table->id();
                $table->string('title')->default('Banner');
                $table->string('image')->nullable();
                $table->string('location')->default('header');
                $table->boolean('is_active')->default(true);
                $table->string('target_url')->nullable();
                $table->text('code')->nullable();
                $table->timestamps();
            });
        }

        // 13. audit_logs
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->string('record_type');
                $table->string('record_title')->nullable();
                $table->text('changed_fields')->nullable();
                $table->timestamps();
            });
        }

        // 14. navigation_items
        if (!Schema::hasTable('navigation_items')) {
            Schema::create('navigation_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('url');
                $table->string('audience')->default('Herkes');
                $table->boolean('is_web')->default(true);
                $table->boolean('is_mobile')->default(true);
                $table->boolean('is_footer')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // 15. site_settings
        if (!Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('site_name')->default('EhliyetGo');
                $table->string('contact_email')->default('destek@ehliyetgo.com');
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('logo')->nullable();
                $table->string('favicon')->nullable();
                $table->timestamps();
            });
        }

        // 16. premium_plans
        if (!Schema::hasTable('premium_plans')) {
            Schema::create('premium_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('short_code')->unique();
                $table->integer('duration_days')->default(30);
                $table->integer('price_cents')->default(9900); // 99 TL
                $table->integer('order')->default(0);
                $table->boolean('is_published')->default(true);
                $table->timestamps();
            });
        }

        // 17. admin_roles
        if (!Schema::hasTable('admin_roles')) {
            Schema::create('admin_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('key')->unique();
                $table->boolean('is_super_admin')->default(false);
                $table->integer('permissions_count')->default(25);
                $table->boolean('is_system')->default(true);
                $table->timestamps();
            });
        }

        // Add additional columns to existing tables
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) $table->string('phone')->nullable();
            if (!Schema::hasColumn('users', 'role_name')) $table->string('role_name')->default('Öğrenci');
            if (!Schema::hasColumn('users', 'premium_until')) $table->timestamp('premium_until')->nullable();
            if (!Schema::hasColumn('users', 'last_active_at')) $table->timestamp('last_active_at')->nullable();
        });

        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'mode')) $table->string('mode')->default('Deneme');
            if (!Schema::hasColumn('exams', 'is_public')) $table->boolean('is_public')->default(true);
            if (!Schema::hasColumn('exams', 'is_shuffle')) $table->boolean('is_shuffle')->default(false);
            if (!Schema::hasColumn('exams', 'question_topic_id')) $table->foreignId('question_topic_id')->nullable()->constrained('question_topics')->nullOnDelete();
        });

        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'type')) $table->string('type')->default('Çoktan Seçmeli');
            if (!Schema::hasColumn('questions', 'status')) $table->string('status')->default('Yayında');
            if (!Schema::hasColumn('questions', 'difficulty')) $table->string('difficulty')->default('Orta');
            if (!Schema::hasColumn('questions', 'question_topic_id')) $table->foreignId('question_topic_id')->nullable()->constrained('question_topics')->nullOnDelete();
        });
    }

    public function down(): void
    {
    }
};
