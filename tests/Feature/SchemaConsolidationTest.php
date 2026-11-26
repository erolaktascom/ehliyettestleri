<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\{Artisan, DB, Schema};
use Tests\TestCase;

class SchemaConsolidationTest extends TestCase
{
    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = config('database.default');
        config(['database.connections.legacy_cleanup' => array_merge(config('database.connections.sqlite'), ['database' => ':memory:'])]);
        DB::setDefaultConnection('legacy_cleanup');
        $paths = array_values(array_filter(glob(database_path('migrations/*.php')), fn ($path) => basename($path) < '2026_10_29_250000_consolidate_content_schema.php'));
        Artisan::call('migrate', ['--database' => 'legacy_cleanup', '--path' => $paths, '--realpath' => true, '--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->previousConnection);
        DB::purge('legacy_cleanup');
        parent::tearDown();
    }

    private function migrateCleanup(): void
    {
        Artisan::call('migrate', ['--database' => 'legacy_cleanup', '--force' => true]);
    }

    public function test_cleanup_preserves_options_positions_history_media_and_legacy_lessons(): void
    {
        $stamp = '2026-10-10 12:34:56';
        $exam = DB::table('exams')->insertGetId(['title' => 'Sınav', 'slug' => 'sinav']);
        $category = DB::table('topic_categories')->insertGetId(['name' => 'Sorular', 'slug' => 'sorular']);
        $test = DB::table('topic_tests')->insertGetId(['title' => 'Test', 'slug' => 'test', 'topic_category_id' => $category]);
        $one = DB::table('questions')->insertGetId([
            'question_text' => '<p>Birinci soru</p>', 'option_a' => 'Evet', 'option_b' => 'Hayır',
            'correct_option' => 'B', 'exam_id' => $exam, 'topic_test_id' => $test, 'order' => 3,
            'topic_category_id' => $category, 'difficulty' => 'Kolay', 'image_url' => '/images/soru.png', 'media_type' => 'image',
            'created_at' => $stamp, 'updated_at' => $stamp, 'first_published_at' => $stamp, 'last_editor_name' => 'Editör',
        ]);
        $options = array_map(fn ($key) => ['key' => $key, 'text' => 'Şık '.$key], range('A', 'F'));
        $two = DB::table('questions')->insertGetId([
            'question_text' => '<p>İkinci soru</p>', 'options' => json_encode($options), 'correct_option' => 'F',
            'option_a' => 'Eski değer', 'exam_id' => $exam, 'order' => 1,
            'image_url' => 'https://youtu.be/abc123', 'media_type' => 'video',
        ]);
        DB::table('exam_question')->insert(['exam_id' => $exam, 'question_id' => $one, 'position' => 7]);
        DB::table('question_topic_test')->insert(['topic_test_id' => $test, 'question_id' => $one, 'position' => 4]);
        $attempt = DB::table('exam_attempts')->insertGetId(['exam_id' => $exam, 'question_ids' => json_encode([$two, $one]), 'total_questions' => 2, 'started_at' => $stamp, 'expires_at' => '2026-10-10 13:34:56']);
        DB::table('exam_attempt_answers')->insert(['exam_attempt_id' => $attempt, 'question_id' => $one, 'selected_option' => 'B', 'is_correct' => true]);
        $lectureCategory = DB::table('lecture_categories')->insertGetId(['name' => 'Güncel kategori', 'slug' => 'guncel-kategori']);
        $legacyCategory = DB::table('content_categories')->insertGetId(['name' => 'Eski kategori', 'slug' => 'eski-kategori']);
        $lecture = DB::table('lectures')->insertGetId(['lecture_category_id' => $lectureCategory, 'title' => 'Aynı', 'slug' => 'ayni', 'summary' => 'Özet', 'content' => '<p>Metin</p>', 'updated_at' => $stamp]);
        DB::table('lectures')->insert(['lecture_category_id' => $lectureCategory, 'title' => 'Güncel', 'slug' => 'cakisan', 'content' => '<p>Yeni metin</p>']);
        DB::table('topics')->insert([
            ['content_category_id' => $legacyCategory, 'title' => 'Aynı', 'slug' => 'ayni', 'short_description' => 'Özet', 'content' => '<p>Metin</p>', 'status' => 'published', 'view_count' => 142],
            ['content_category_id' => $legacyCategory, 'title' => 'Yalnız eski tabloda', 'slug' => 'kayip-ders', 'short_description' => 'Korunacak', 'content' => '<p>Korunacak içerik</p>', 'status' => 'published', 'view_count' => 285],
            ['content_category_id' => $legacyCategory, 'title' => 'Eski', 'slug' => 'cakisan', 'short_description' => null, 'content' => '<p>Eski metin</p>', 'status' => 'published', 'view_count' => 12],
        ]);
        $ad = DB::table('advertisements')->insertGetId(['title' => 'Görsel reklam', 'image' => '/reklam.png', 'location' => 'Header']);
        $blog = DB::table('blog_posts')->insertGetId(['title' => 'Yazı', 'slug' => 'yazi', 'content' => '<p>Yazı</p>', 'image_url' => '/blog.png']);
        $this->migrateCleanup();

        $q = DB::table('questions')->find($one);
        $this->assertSame([['key' => 'A', 'text' => 'Evet'], ['key' => 'B', 'text' => 'Hayır']], json_decode($q->options, true));
        foreach (['created_at', 'updated_at', 'first_published_at'] as $field) $this->assertSame($stamp, $q->{$field});
        $this->assertSame('Editör', $q->last_editor_name);
        $this->assertStringContainsString('/images/soru.png', $q->question_text);
        $this->assertSame($options, json_decode(DB::table('questions')->find($two)->options, true));
        $this->assertStringContainsString('https://www.youtube.com/embed/abc123', DB::table('questions')->find($two)->question_text);
        $this->assertSame(7, DB::table('exam_question')->where('question_id', $one)->value('position'));
        $this->assertSame(8, DB::table('exam_question')->where('question_id', $two)->value('position'));
        $this->assertSame(4, DB::table('question_topic_test')->where('question_id', $one)->value('position'));
        $this->assertSame([$two, $one], json_decode(DB::table('exam_attempts')->find($attempt)->question_ids));
        $this->assertSame('B', DB::table('exam_attempt_answers')->where('question_id', $one)->value('selected_option'));
        $this->assertSame(142, DB::table('lectures')->find($lecture)->view_count);
        $this->assertSame($stamp, DB::table('lectures')->find($lecture)->updated_at);
        $imported = DB::table('lectures')->where('slug', 'kayip-ders')->first();
        $this->assertSame('<p>Korunacak içerik</p>', $imported->content);
        $this->assertSame(285, $imported->view_count);
        $this->assertSame($lectureCategory, $imported->lecture_category_id);
        $this->assertFalse((bool) $imported->is_published);
        $this->assertSame('<p>Yeni metin</p>', DB::table('lectures')->where('slug', 'cakisan')->value('content'));
        $conflict = DB::table('lectures')->where('slug', 'like', 'cakisan-eski-%')->first();
        $this->assertSame('<p>Eski metin</p>', $conflict->content);
        $this->assertFalse((bool) $conflict->is_published);
        $this->assertSame(1, DB::table('lecture_categories')->count());
        $this->assertStringContainsString('/reklam.png', DB::table('advertisements')->find($ad)->code);
        $this->assertStringContainsString('/blog.png', DB::table('blog_posts')->find($blog)->content);
        foreach (['topics', 'content_categories', 'question_topics'] as $table) $this->assertFalse(Schema::hasTable($table));
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        $this->assertSame('ok', DB::select('PRAGMA integrity_check')[0]->integrity_check);
    }

    public function test_cleanup_rolls_back_instead_of_discarding_unknown_integration_settings(): void
    {
        DB::table('integrations')->update(['settings' => '{"legacy_key":"keep-me"}']);
        try {
            $this->migrateCleanup();
            $this->fail('Unknown configuration must stop consolidation.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Legacy integration settings', $exception->getMessage());
        }
        $this->assertTrue(Schema::hasTable('topics'));
        $this->assertTrue(Schema::hasColumn('questions', 'option_a'));
        $this->assertFalse(Schema::hasColumn('lectures', 'view_count'));
        $this->assertSame('{"legacy_key":"keep-me"}', DB::table('integrations')->value('settings'));
    }
}
