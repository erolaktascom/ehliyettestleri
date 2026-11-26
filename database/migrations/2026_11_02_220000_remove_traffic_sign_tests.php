<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            $slugs=['trafik-isaretleri-test-1','trafik-isaretleri-test-2','trafik-isaretleri-test-3'];
            $ids=DB::table('topic_tests')->where('section','traffic_signs')->whereIn('slug',$slugs)->pluck('id');
            if (DB::table('topic_tests')->where('section','traffic_signs')->whereNotIn('id',$ids)->exists()) {
                throw new RuntimeException('Beklenmeyen trafik işareti testleri var; veri silmeden önce inceleyin.');
            }
            $questions=DB::table('question_topic_test')->whereIn('topic_test_id',$ids)->pluck('question_id')->unique();
            // Only the generated questions, never questions borrowed from another test or exam.
            $questions=DB::table('questions')->whereIn('id',$questions)
                ->where('question_text','like','%trafik işaretinin anlamı aşağıdakilerden hangisidir?%')->pluck('id');
            $shared=DB::table('question_topic_test')->whereNotIn('topic_test_id',$ids)->whereIn('question_id',$questions)->pluck('question_id')
                ->merge(DB::table('exam_question')->whereIn('question_id',$questions)->pluck('question_id'));
            $otherAttempts=DB::table('exam_attempts')->where(fn($q)=>$q->whereNull('topic_test_id')->orWhereNotIn('topic_test_id',$ids))->get();
            foreach ($otherAttempts as $attempt) $shared=$shared->merge(json_decode($attempt->question_ids??'[]',true)??[]);
            $questions=$questions->diff($shared)->values();
            DB::table('exam_attempts')->whereIn('topic_test_id',$ids)->delete();
            DB::table('topic_tests')->whereIn('id',$ids)->delete();
            DB::table('questions')->whereIn('id',$questions)->delete();
            DB::table('seo_redirects')->where('source_model',App\Models\TopicTest::class)->whereIn('source_id',$ids)->delete();
            DB::table('seo_route_settings')->whereIn('route_key',['traffic-signs.tests','traffic-signs.play'])->delete();
            foreach(DB::table('seo_settings')->get() as $setting) {
                $sections=json_decode($setting->sitemap_sections??'null',true);
                if(is_array($sections)) DB::table('seo_settings')->where('id',$setting->id)->update([
                    'sitemap_sections'=>json_encode(array_values(array_diff($sections,['trafik-isareti-testleri'])))
                ]);
            }
            foreach(DB::table('navigation_items')->get() as $item) {
                $path=parse_url($item->url,PHP_URL_PATH);
                $host=parse_url($item->url,PHP_URL_HOST);
                if ($host && $host!==parse_url(config('app.url'),PHP_URL_HOST)) continue;
                if ($path==='/trafik-isaretleri-testleri' || str_starts_with($path??'','/trafik-isaretleri-testleri/')) {
                    DB::table('navigation_items')->where('id',$item->id)->delete();
                }
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Silinen test ve sorular migration öncesindeki veritabanı yedeğinden geri alınabilir.');
    }
};