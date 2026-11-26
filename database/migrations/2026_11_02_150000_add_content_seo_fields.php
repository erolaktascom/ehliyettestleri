<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = ['exams', 'topic_tests', 'blog_posts', 'lectures', 'pages', 'topic_categories', 'lecture_categories', 'blog_categories', 'traffic_sign_categories'];
    public function up(): void
    {
        foreach ($this->tables as $name) {
            $existingTitle = Schema::hasColumn($name, 'seo_title');
            $existingDescription = Schema::hasColumn($name, 'seo_description');
            Schema::table($name, function (Blueprint $table) use ($existingTitle, $existingDescription) {
                if (!$existingTitle) $table->string('seo_title')->nullable();
                if (!$existingDescription) $table->text('seo_description')->nullable();
                $table->string('seo_canonical', 2048)->nullable();
                $table->string('seo_index')->default('index');
            });
        }
        Schema::create('seo_route_settings', function (Blueprint $table) {
            $table->id();
            $table->string('route_key')->unique();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_canonical', 2048)->nullable();
            $table->string('seo_index')->default('index');
            $table->timestamps();
        });
        Schema::table('seo_settings', fn (Blueprint $table) => $table->dropColumn('keywords'));
    }
    public function down(): void
    {
        foreach ($this->tables as $name) {
            $columns = ['seo_canonical', 'seo_index'];
            if (!in_array($name, ['pages', 'lectures'])) $columns = [...$columns, 'seo_title', 'seo_description'];
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn($columns));
        }
        Schema::dropIfExists('seo_route_settings');
        Schema::table('seo_settings', fn (Blueprint $table) => $table->text('keywords')->nullable());
    }
};