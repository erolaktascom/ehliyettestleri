<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};
use App\Models\TopicTest;
use App\Support\AutomaticSlugRedirects;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('topic_tests', fn (Blueprint $table) => $table->string('section')->default('topic_tests')->index());
        DB::transaction(function () {
            // Only the explicit former animation category is moved. General traffic tests stay put.
            $tests = TopicTest::whereHas('category', fn ($q) => $q->where('slug', 'animasyonlu-sorular'))->get();
            foreach ($tests as $test) {
                // Preserve content dates, creators, question pivots and existing attempts.
                DB::table('topic_tests')->where('id', $test->id)->update(['section' => 'animated']);
                $test->section = 'animated';
                $test->syncChanges();
                AutomaticSlugRedirects::changed($test);
            }
            $category = DB::table('topic_categories')->where('slug', 'animasyonlu-sorular')->first();
            if ($category) {
                if (!DB::table('seo_route_settings')->where('route_key', 'animated-questions.index')->exists()) {
                    DB::table('seo_route_settings')->insert([
                        'route_key' => 'animated-questions.index', 'seo_title' => $category->seo_title,
                        'seo_description' => $category->seo_description, 'seo_index' => $category->seo_index,
                        'seo_canonical' => $category->seo_canonical, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                DB::table('navigation_items')->where('url', '/konu-testleri/animasyonlu-sorular')
                    ->update(['url' => '/animasyonlu-sorular']);
            }
            // Preserve the previous inclusion choice for these tests in the sitemap.
            foreach (DB::table('seo_settings')->get() as $setting) {
                $sections = json_decode($setting->sitemap_sections ?? 'null', true);
                if (is_array($sections) && in_array('konu-testleri', $sections, true)) {
                    DB::table('seo_settings')->where('id', $setting->id)->update(['sitemap_sections' => json_encode(array_values(array_unique([...$sections, 'animasyonlu-testler', 'trafik-isareti-testleri'])))]);
                }
            }
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('Bölüm ve yönlendirme verilerini geri almak için migration öncesi yedeği kullanın.');
    }
};
