<?php
namespace Tests\Feature;

use App\Livewire\Admin\{TopicTestForm, TopicTests};
use App\Models\{TopicCategory, TopicTest, User, ExamAttempt, SeoRedirect, SeoSetting};
use App\Support\{SiteSitemap, AutomaticSlugRedirects};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Database\Schema\Blueprint;
use Livewire\Livewire;
use Tests\TestCase;

class TopicTestSectionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeTest(string $section, ?TopicCategory $category = null): TopicTest
    {
        $category ??= TopicCategory::create(['name' => 'Kategori '.$section, 'slug' => 'kategori-'.$section, 'is_active' => true]);
        $test = TopicTest::create(['title' => 'Özel Test '.$section, 'slug' => 'test-'.$section,
            'section' => $section, 'topic_category_id' => $category->id, 'is_published' => true]);
        $test->questions()->create(['topic_category_id' => $category->id, 'question_text' => 'Korunan soru?',
            'options' => [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki']], 'correct_option' => 'A', 'status' => 'Yayında']);
        return $test;
    }

    public function test_public_lists_are_explicit_and_do_not_leak_across_sections(): void
    {
        $normal = $this->makeTest('topic_tests');
        $animated = $this->makeTest('animated', $normal->category);
        foreach (['topic_tests' => route('topic-tests.category', $normal->category), 'animated' => '/animasyonlu-sorular'] as $section => $path) {
            $this->get($path)->assertOk()->assertViewHas('tests', fn ($tests) => $tests->count() === 1 && $tests->first()->section === $section);
        }
        $specialOnly = TopicCategory::create(['name' => 'Yalnız özel', 'slug' => 'yalniz-ozel', 'is_active' => true]);
        $animated->update(['topic_category_id' => $specialOnly->id]);
        $this->get('/konu-testleri')->assertViewHas('categories', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $normal->topic_category_id && $rows->first()->topic_tests_count === 1);
        $specialOnly->update(['is_active' => false]);
        $this->get('/animasyonlu-sorular')->assertViewHas('tests', fn ($rows) => $rows->isEmpty());
        $this->post(route('topic-tests.start', $animated))->assertNotFound();
    }

    public function test_special_test_links_canonical_attempt_answers_and_result_use_the_same_engine(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['animated'] as $section) {
            $test = $this->makeTest($section);
            $path = AutomaticSlugRedirects::publicPath($test);
            $this->get($path)->assertRedirect($test->publicUrl());
            $this->get(route('topic-tests.play', ['category' => $test->category->slug, 'test' => $test->slug]))->assertNotFound();
            $attempt = ExamAttempt::where('topic_test_id', $test->id)->sole();
            $this->get($path)->assertSee('name="robots" content="noindex, nofollow"',false)->assertDontSee('rel="canonical"',false)->assertViewHas('attempt', fn ($row) => $row->id === $attempt->id)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            $this->postJson(route('exams.save-answer', $attempt), ['question_id' => $test->questions->first()->id, 'selected_option' => 'A'])->assertOk();
            $this->post(route('exams.finish', $attempt))->assertRedirect(route('exams.result', $attempt));
            $this->get(route('exams.result', $attempt))->assertOk()->assertSee('href="'.$test->listUrl().'"', false);
            $this->assertSame('A', $attempt->answers()->sole()->selected_option);
            $this->assertSame(1, $attempt->fresh()->correct_count);
        }
    }

    public function test_moving_a_test_preserves_attempts_and_deleted_redirects_really_return_404(): void
    {
        $test = $this->makeTest('topic_tests');
        $this->actingAs(User::factory()->create());
        $this->post(route('topic-tests.start', $test));
        $attempt = ExamAttempt::sole();
        $old = AutomaticSlugRedirects::publicPath($test);
        $questionIds = $attempt->question_ids;
        $test->update(['section' => 'animated']);
        $this->get($old)->assertStatus(301)->assertRedirect(AutomaticSlugRedirects::publicPath($test));
        $this->get($test->publicUrl())->assertViewHas('attempt', fn ($row) => $row->id === $attempt->id);
        $this->assertSame($questionIds, $attempt->fresh()->question_ids);
        SeoRedirect::where('old_url', $old)->delete();
        $this->get($old)->assertNotFound();
        $specialPath = AutomaticSlugRedirects::publicPath($test);
        $test->category->update(['slug' => 'yeni-kategori']);
        $this->assertSame($specialPath, AutomaticSlugRedirects::publicPath($test));
        $this->assertFalse(SeoRedirect::where('old_url', $specialPath)->exists());
        $test->update(['section' => 'topic_tests', 'slug' => 'yeni-test']);
        $this->get($specialPath)->assertRedirect('/konu-testleri/yeni-kategori/yeni-test');
        $this->assertFalse(SeoRedirect::where('old_url', $old)->exists());
    }

    public function test_panel_section_save_filter_validation_and_live_link(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $test = $this->makeTest('topic_tests');
        Livewire::test(TopicTestForm::class, ['id' => $test->id])->set('section', 'animated')->call('save')->assertHasNoErrors()->assertNoRedirect();
        Livewire::test(TopicTestForm::class, ['id' => $test->id])->assertSet('section', 'animated')->assertSee('/animasyonlu-sorular/'.$test->slug)
            ->set('section', 'traffic_signs')->call('save')->assertHasErrors('section');
        $this->assertSame('animated', $test->fresh()->section);
        Livewire::test(TopicTests::class)->set('selectedSection', 'animated')
            ->assertViewHas('tests', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $test->id);
    }

    public function test_search_and_sitemap_use_only_the_actual_section_urls_and_respect_noindex(): void
    {
        SeoSetting::create(['sitemap_enabled' => true]);
        $normal = $this->makeTest('topic_tests');
        $animated = $this->makeTest('animated');
        foreach (['konu-testleri' => $normal, 'animasyonlu-testler' => $animated] as $section => $test) {
            $entries = iterator_to_array((new SiteSitemap)->entries($section));
            $this->assertSame([], $entries);
            $this->get(SiteSitemap::path($section))->assertNotFound();
            $this->get('/arama?q='.urlencode($test->title))->assertOk()->assertSee('href="'.$test->publicUrl().'"', false);
        }
        $this->get('/soru-kategorileri-sitemap.xml')->assertOk()->assertDontSee('/konu-testleri/'.$animated->category->slug, false);
        $animated->update(['seo_index' => 'noindex']);
        $this->assertSame([], iterator_to_array((new SiteSitemap)->entries('animasyonlu-testler')));
    }

    public function test_data_migration_preserves_traffic_tests_content_dates_and_question_links(): void
    {
        $category = TopicCategory::create(['name' => 'Animasyonlu Sorular', 'slug' => 'animasyonlu-sorular', 'is_active' => true]);
        $animated = $this->makeTest('topic_tests', $category);
        $trafficCategory = TopicCategory::create(['name' => 'Trafik ve Çevre', 'slug' => 'trafik-ve-cevre', 'is_active' => true]);
        $traffic = TopicTest::create(['title' => 'Trafik', 'slug' => 'trafik-testi', 'topic_category_id' => $trafficCategory->id, 'is_published' => true]);
        $before = DB::table('topic_tests')->where('id', $animated->id)->first();
        $pivots = DB::table('question_topic_test')->get()->toJson();
        Schema::table('topic_tests', function (Blueprint $table) { $table->dropUnique('topic_tests_category_slug_unique'); $table->dropUnique('topic_tests_animated_slug_unique'); $table->dropIndex(['section']);
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) $table->dropColumn(['category_slug_scope', 'animated_slug_scope']);
            $table->dropColumn('section'); });
        $migration = require database_path('migrations/2026_11_02_210000_add_topic_test_sections.php');
        $migration->up();
        $this->assertSame('animated', $animated->fresh()->section);
        $this->assertSame('topic_tests', $traffic->fresh()->section);
        $this->assertSame($pivots, DB::table('question_topic_test')->get()->toJson());
        foreach (['created_at', 'updated_at', 'first_published_at', 'content_updated_at', 'created_by', 'last_edited_by'] as $field) {
            $this->assertSame($before->$field, DB::table('topic_tests')->where('id', $animated->id)->value($field));
        }
        $this->get('/konu-testleri/animasyonlu-sorular/'.$animated->slug)->assertRedirect('/animasyonlu-sorular/'.$animated->slug);
    }
}