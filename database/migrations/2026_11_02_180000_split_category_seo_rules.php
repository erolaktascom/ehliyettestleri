<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('seo_settings')->get(['id', 'content_rules']) as $setting) {
            $rules = json_decode($setting->content_rules ?? 'null', true);
            if (!is_array($rules) || !array_key_exists('categories', $rules)) continue;
            foreach (['topic_categories', 'lecture_categories', 'blog_categories', 'traffic_sign_categories'] as $key) {
                if (!array_key_exists($key, $rules)) $rules[$key] = $rules['categories'];
            }
            unset($rules['categories']);
            // Query builder keeps settings timestamps and all content/editor metadata unchanged.
            DB::table('seo_settings')->where('id', $setting->id)->update(['content_rules' => json_encode($rules, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        throw new \RuntimeException('Ayrı kategori SEO kuralları tek kurala kayıpsız birleştirilemez. Geri dönüş için migration öncesi yedeği kullanın.');
    }
};