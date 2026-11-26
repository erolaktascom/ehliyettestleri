<?php

use App\Support\SeoTemplates;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            $setting = DB::table('seo_settings')->orderBy('id')->first();
            $saved = json_decode($setting->content_rules ?? 'null', true, flags: JSON_THROW_ON_ERROR);
            $rules = SeoTemplates::values($saved);

            foreach (['exams', 'topic_tests', 'lectures', 'blog_posts', 'topic_categories', 'lecture_categories',
                'blog_categories', 'traffic_sign_categories', 'pages', 'seo_route_settings'] as $table) {
                DB::table($table)->where('seo_index', 'inherit')
                    ->where(fn ($query) => $query->whereRaw("TRIM(seo_title) <> ''")
                        ->orWhereRaw("TRIM(seo_description) <> ''"))
                    ->update(['seo_index' => $rules[$table]['index'] ?? 'index']);
            }

            if (($saved['pagination']['index'] ?? null) === 'inherit') {
                $saved['pagination']['index'] = 'index';
                DB::table('seo_settings')->where('id', $setting->id)
                    ->update(['content_rules' => json_encode($saved, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
            }
        });
    }

    public function down(): void
    {
        // Explicit choices made before or after this migration cannot be distinguished.
    }
};
