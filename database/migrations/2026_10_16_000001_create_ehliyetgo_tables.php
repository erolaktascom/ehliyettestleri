<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Users modification
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('password');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
        });

        // 2. Exams (Deneme Sınavları)
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('question_count')->default(50);
            $table->unsignedInteger('duration_minutes')->default(45);
            $table->unsignedInteger('passing_score')->default(70);
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // 3. Topic Categories (Konu Testi Kategorileri)
        Schema::create('topic_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        // 4. Topic Tests (Konu Testleri)
        Schema::create('topic_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('question_count')->default(10);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        // 5. Questions (Sorular)
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_test_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question_text');
            $table->string('image_url')->nullable();
            $table->string('media_type')->default('none'); // 'none', 'image', 'video'
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->string('correct_option', 2); // 'A', 'B', 'C', 'D'
            $table->text('explanation')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        // 6. Exam Attempts (Sınav Girişleri)
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_test_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->unsignedInteger('empty_count')->default(0);
            $table->decimal('score', 5, 2)->default(0);
            $table->boolean('is_passed')->default(false);
            $table->string('status')->default('in_progress'); // in_progress, completed, timed_out
            $table->timestamps();
        });

        // 7. Exam Attempt Answers (Verilen Cevaplar)
        Schema::create('exam_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('selected_option', 2)->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'question_id']);
        });

        // 8. Traffic Sign Categories
        Schema::create('traffic_sign_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        // 9. Traffic Signs (Trafik İşaretleri)
        Schema::create('traffic_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traffic_sign_category_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable(); // e.g. T-1
            $table->string('name');
            $table->text('meaning');
            $table->string('image_path')->nullable();
            $table->text('svg_icon')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        // 10. User Sign Reviews (Trafik İşareti Çalışma Durumları)
        Schema::create('user_sign_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->foreignId('traffic_sign_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('review'); // known, review
            $table->timestamps();

            $table->index(['user_id', 'traffic_sign_id']);
            $table->index(['session_id', 'traffic_sign_id']);
        });

        // 11. Lecture Categories (Konu Anlatımı Kategorileri)
        Schema::create('lecture_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        // 12. Lectures (Konular / Dersler)
        Schema::create('lectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecture_category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        // 13. User Lecture Progress
        Schema::create('user_lecture_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->foreignId('lecture_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'lecture_id']);
            $table->index(['session_id', 'lecture_id']);
        });

        // 14. Blog Categories
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // 15. Blog Posts
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('author_name')->default('EhliyetGo');
            $table->string('image_url')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });

        // 16. Contact Messages
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('user_lecture_progress');
        Schema::dropIfExists('lectures');
        Schema::dropIfExists('lecture_categories');
        Schema::dropIfExists('user_sign_reviews');
        Schema::dropIfExists('traffic_signs');
        Schema::dropIfExists('traffic_sign_categories');
        Schema::dropIfExists('exam_attempt_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('topic_tests');
        Schema::dropIfExists('topic_categories');
        Schema::dropIfExists('exams');
    }
};
