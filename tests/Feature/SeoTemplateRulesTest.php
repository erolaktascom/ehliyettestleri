<?php
namespace Tests\Feature;

use App\Models\{Exam, TopicTest, BlogPost, Lecture, Page, TopicCategory, LectureCategory, BlogCategory, TrafficSignCategory, SeoRouteSetting, SeoSetting, User};
use App\Livewire\Admin\{SeoSettings, PageForm};
use App\Support\{SeoTemplates, AutomaticSlugRedirects, SeoPageCatalog};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class SeoTemplateRulesTest extends TestCase
{
    public function test_switching_sections_keeps_drafts_and_reopens_the_rule_that_needs_correction(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $form = Livewire::test(SeoSettings::class)->assertSet('selectedType', 'blog_posts')
            ->set('content_rules.blog_posts.title', '%gecersiz%')
            ->set('selectedType', 'authors')->set('section', 'pagination')
            ->set('pagination_index', 'noindex')->call('save')
            ->assertHasErrors('content_rules.blog_posts.title')
            ->assertSet('section', 'rules')->assertSet('selectedType', 'blog_posts');
        $form->set('content_rules.blog_posts.title', '%baslik%')->call('save')
            ->assertHasNoErrors()->assertNoRedirect();
        Livewire::test(SeoSettings::class)->assertSet('pagination_index', 'noindex')
            ->assertSet('content_rules.blog_posts.title', '%baslik%');
    }

    use RefreshDatabase;

    private function readHead(string $url): array
    {
        $html = $this->get($url)->assertOk()->getContent();
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        $values = [];
        foreach (['title' => '//head/title', 'description' => '//head/meta[@name="description"]/@content', 'robots' => '//head/meta[@name="robots"]/@content', 'canonical' => '//head/link[@rel="canonical"]/@href'] as $key => $query) {
            $values[$key] = $xpath->query($query)->item(0)?->nodeValue;
        }
        return $values;
    }

    public function test_all_content_types_use_matching_rules_and_canonical_remains_their_own_url(): void
    {
        $this->seed(DatabaseSeeder::class);
        $rules = SeoTemplates::values();
        foreach ($rules as $type => &$rule) $rule = ['title' => $type.' %baslik%', 'description' => $type.' %kategori% %yil%', 'index' => 'noindex'];
        unset($rule);
        config(['app.url' => 'https://example.com']);
        SeoSetting::first()->update(['content_rules' => $rules, 'canonical_base' => 'https://example.com']);
        foreach ([Exam::class => 'exams', TopicTest::class => 'topic_tests', BlogPost::class => 'blog_posts', Lecture::class => 'lectures', TopicCategory::class => 'topic_categories', LectureCategory::class => 'lecture_categories', BlogCategory::class => 'blog_categories', TrafficSignCategory::class => 'traffic_sign_categories'] as $class => $type) {
            $record = $class::firstOrFail();
            $record->update(['seo_title' => '', 'seo_description' => '', 'seo_index' => 'inherit']);
            if ($record instanceof Exam || $record instanceof TopicTest) {
                // Template configuration remains usable in panel previews; personal play pages remain private.
                $preview = SeoTemplates::preview($type, $rules[$type], 'Site');
                $this->assertSame($type.' '.$record->title, $preview['title']);
                $this->assertSame($type.' '.($record->category?->name ? $record->category->name.' ' : '').now()->format('Y'), $preview['description']);
                $this->actingAs(\App\Models\User::factory()->create());
                $record->questions()->create(['question_text'=>'Özel oturum sorusu','status'=>'Yayında','options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']],'correct_option'=>'A']);
                $path=AutomaticSlugRedirects::publicPath($record);
                $this->get($path)->assertRedirect(url($path));
                $head=$this->readHead($path);
                $this->assertSame($type.' '.$record->title,$head['title']);
                $this->assertSame($preview['description'],$head['description']);
                $this->assertSame('noindex, nofollow',$head['robots']);
                $this->assertNull($head['canonical']);
                auth()->logout();
                continue;
            }
            $head = $this->readHead(AutomaticSlugRedirects::publicPath($record));
            $this->assertSame($type.' '.($record->title ?: $record->name), $head['title'], $class);
            $this->assertStringStartsWith($type.' ', $head['description']);
            $this->assertStringContainsString(now()->format('Y'), $head['description']);
            $this->assertSame('noindex, follow', $head['robots']);
            $this->assertSame('https://example.com'.AutomaticSlugRedirects::publicPath($record), $head['canonical']);
        }
        foreach (SeoPageCatalog::PAGES as $route => $label) {
            $head = $this->readHead(route($route));
            $this->assertStringNotContainsString('static_pages ', $head['title']);
            $this->assertSame('index, follow', $head['robots']);
        }
        $post = BlogPost::first();
        $this->assertSame('authors '.$post->author_name, $this->readHead('/yazar/'.\Illuminate\Support\Str::slug($post->author_name))['title']);
    }

    public function test_per_field_overrides_dynamic_inheritance_and_automatic_fallback(): void
    {
        $rules = SeoTemplates::values();
        $rules['topic_categories'] = ['title' => 'Genel %baslik%', 'description' => '%ozet%', 'index' => 'noindex'];
        config(['app.url' => 'https://example.com']);
        $setting = SeoSetting::create(['site_title' => 'Site', 'content_rules' => $rules, 'canonical_base' => 'https://example.com']);
        $page = TopicCategory::create(['name' => 'Bilgi', 'is_active' => true, 'slug' => 'bilgi', 'description' => 'Sayfanın özeti', 'seo_title' => 'Özel Başlık']);
        $this->assertSame('noindex', $page->fresh()->seo_index);
        $head = $this->readHead('/konu-testleri/bilgi');
        $this->assertSame('Özel Başlık', $head['title']);
        $this->assertSame('Sayfanın özeti', $head['description']);
        $this->assertSame('noindex, follow', $head['robots']);
        $page->update(['seo_title' => '', 'seo_description' => 'Özel Açıklama', 'seo_index' => 'index']);
        $head = $this->readHead('/konu-testleri/bilgi');
        $this->assertSame('Genel Bilgi', $head['title']);
        $this->assertSame('Özel Açıklama', $head['description']);
        $this->assertSame('index, follow', $head['robots']);
        $rules['topic_categories']['title'] = 'Yeni %baslik%';
        $rules['topic_categories']['index'] = 'index';
        $setting->update(['content_rules' => $rules]);
        $page->update(['seo_index' => 'noindex']);
        $this->assertSame('Yeni Bilgi', $this->readHead('/konu-testleri/bilgi')['title']);
        $this->assertSame('noindex, follow', $this->readHead('/konu-testleri/bilgi')['robots']);
        $page->update(['seo_index' => 'inherit', 'seo_description' => '']);
        $setting->update(['content_rules' => null]);
        $head = $this->readHead('/konu-testleri/bilgi');
        $this->assertStringContainsString('Bilgi', $head['title']);
        $this->assertSame('Sayfanın özeti', $head['description']);
        $this->assertSame('index, follow', $head['robots']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $standalone = Page::create(['title' => 'Sayfa', 'slug' => 'bagimsiz-sayfa']);
        Livewire::test(PageForm::class)->assertSet('seo_index', 'index')->assertDontSee('Genel ayarı kullan');
        Livewire::test(PageForm::class, ['id' => $standalone->id])->set('seo_index', 'noindex')->call('save')->assertHasNoErrors()->assertNoRedirect();
        Livewire::test(PageForm::class, ['id' => $standalone->id])->assertSet('seo_index', 'noindex');
    }
    public function test_general_rules_save_reopen_validate_and_preserve_verification(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        SeoSetting::create(['site_title' => 'Site', 'verification_tags' => ['google-site-verification' => 'keep']]);
        Livewire::test(SeoSettings::class)->set('content_rules.blog_posts.title', '%baslik% | %site_adi%')
            ->set('content_rules.blog_posts.description', '%ozet% %yil%')->set('content_rules.blog_posts.index', 'noindex')
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        Livewire::test(SeoSettings::class)->assertSet('content_rules.blog_posts.title', '%baslik% | %site_adi%')
            ->assertSet('content_rules.blog_posts.index', 'noindex')->assertSee('Önizleme için bu bölümde henüz içerik yok');
        $this->assertSame(['google-site-verification' => 'keep'], SeoSetting::first()->verification_tags);
        Livewire::test(SeoSettings::class)->set('content_rules.exams.title', '%bilinmeyen%')->call('save')->assertHasErrors('content_rules.exams.title');
        Livewire::test(SeoSettings::class)->set('content_rules.exams.index', 'inherit')->call('save')->assertHasErrors('content_rules.exams.index');
        Livewire::test(SeoSettings::class)->set('content_rules.admin', ['index' => 'index'])->call('save')->assertHasErrors('content_rules');
        $this->assertSame(SeoTemplates::DEFAULT_TITLES['exams'], SeoSetting::first()->content_rules['exams']['title']);
    }

    public function test_templates_are_plain_text_and_do_not_override_private_or_error_robots(): void
    {
        $rules = SeoTemplates::values();
        foreach ($rules as &$rule) $rule = ['title' => '%baslik% <script>notExecutable</script>', 'description' => '%ozet%', 'index' => 'index'];
        unset($rule);
        SeoSetting::create(['site_title' => 'Site', 'content_rules' => $rules]);
        TopicCategory::create(['name' => 'Metin %yil%', 'is_active' => true, 'slug' => 'metin', 'description' => '<b>Özet</b>']);
        $head = $this->readHead('/konu-testleri/metin');
        $this->assertSame('Metin %yil% notExecutable', $head['title']);
        $this->assertSame('Özet', $head['description']);
        $this->get('/konu-testleri/metin')->assertDontSee('<script>notExecutable</script>', false);
        $this->assertSame('noindex, follow', $this->readHead('/arama?q=metin')['robots']);
        $this->get('/eksik-seo-adresi')->assertNotFound()->assertSee('content="noindex, follow"', false)->assertDontSee('notExecutable');
    }

    public function test_migration_moves_only_index_to_inherit_without_changing_content_or_audit_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        SeoRouteSetting::create(['route_key' => 'home']);
        $migration = require database_path('migrations/2026_11_02_160000_add_seo_template_rules.php');
        $migration->down();
        $tables = ['exams', 'topic_tests', 'blog_posts', 'lectures', 'pages', 'topic_categories', 'lecture_categories', 'blog_categories', 'traffic_sign_categories', 'seo_route_settings'];
        $before = [];
        foreach ($tables as $table) {
            DB::table($table)->update(['seo_index' => 'index']);
            $first = DB::table($table)->orderBy('id')->first();
            DB::table($table)->where('id', $first->id)->update(['seo_index' => 'noindex', 'seo_title' => 'Korunacak', 'seo_canonical' => 'https://example.com/keep']);
            $before[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }
        $migration->up();
        $this->assertSame('index', Page::create(['title' => 'Yeni varsayılan', 'slug' => 'yeni-varsayilan'])->fresh()->seo_index);
        DB::table('pages')->where('slug', 'yeni-varsayilan')->delete();
        foreach ($tables as $table) {
            $expected = array_map(function ($row) { if ($row['seo_index'] === 'index') $row['seo_index'] = 'inherit'; return $row; }, $before[$table]);
            $this->assertSame($expected, DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(), $table);

        }
    }
}
