<?php
namespace Tests\Feature;

use App\Models\{Exam, TopicTest, BlogPost, Lecture, Page, TopicCategory, LectureCategory, BlogCategory, TrafficSignCategory, User, SeoSetting, SeoRouteSetting};
use App\Livewire\Admin\{ExamForm, TopicTestForm, BlogPostForm, TopicForm, PageForm, QuestionTopicForm, ContentCategoryForm, BlogCategoryForm, TrafficSignCategoryForm, SeoSettings, SeoPages};
use App\Support\AutomaticSlugRedirects;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ContentSeoTest extends TestCase
{
    use RefreshDatabase;

    private function metadataHead(string $path): array
    {
        $html = $this->get($path)->assertOk()->getContent();
        $dom = new \DOMDocument();
        $old = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($old);
        $xpath = new \DOMXPath($dom);
        $result = [];
        foreach (['title' => '//head/title', 'description' => '//head/meta[@name="description"]/@content', 'canonical' => '//head/link[@rel="canonical"]/@href', 'robots' => '//head/meta[@name="robots"]/@content'] as $key => $query) {
            $nodes = $xpath->query($query);
            $this->assertLessThanOrEqual(1, $nodes->length, $key.' must not be duplicated');
            $result[$key] = $nodes->item(0)?->nodeValue;
        }
        $this->assertSame(0, $xpath->query('//head/meta[@name="keywords"]')->length);
        return $result;
    }

    public function test_all_nine_content_forms_save_reopen_and_publish_seo_metadata(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->create(['is_admin' => true]);
        foreach ([Exam::class => ExamForm::class, TopicTest::class => TopicTestForm::class, BlogPost::class => BlogPostForm::class,
            Lecture::class => TopicForm::class, Page::class => PageForm::class, TopicCategory::class => QuestionTopicForm::class,
            LectureCategory::class => ContentCategoryForm::class, BlogCategory::class => BlogCategoryForm::class,
            TrafficSignCategory::class => TrafficSignCategoryForm::class] as $model => $formClass) {
            $record = $model::firstOrFail();
            $this->actingAs($admin);
            Livewire::test($formClass, ['id' => $record->id])->set('seo_title', 'Özel SEO & Başlık')
                ->set('seo_description', 'Özel açıklama & bilgi')->set('seo_canonical', 'https://example.com/asil-sayfa')
                ->set('seo_index', 'noindex')->call('save')->assertHasNoErrors()->assertNoRedirect();
            $this->assertSame('noindex', $record->fresh()->seo_index);
            Livewire::test($formClass, ['id' => $record->id])->assertSet('seo_title', 'Özel SEO & Başlık')
                ->assertSet('seo_description', 'Özel açıklama & bilgi')->assertSet('seo_canonical', 'https://example.com/asil-sayfa')->assertSet('seo_index', 'noindex');
            if ($record instanceof Exam || $record instanceof TopicTest) {
                $record->questions()->create(['question_text'=>'SEO oturum sorusu','status'=>'Yayında','options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']],'correct_option'=>'A']);
                $path=AutomaticSlugRedirects::publicPath($record->fresh());
                $this->get($path)->assertRedirect(url($path));
                $head=$this->metadataHead($path);
                $this->assertSame(['title'=>'Özel SEO & Başlık','description'=>'Özel açıklama & bilgi','canonical'=>null,'robots'=>'noindex, nofollow'],$head,$model);
                auth()->logout();
                continue;
            }
            auth()->logout();
            $head = $this->metadataHead(AutomaticSlugRedirects::publicPath($record->fresh()));
            $this->assertSame(['title' => 'Özel SEO & Başlık', 'description' => 'Özel açıklama & bilgi', 'canonical' => 'https://example.com/asil-sayfa', 'robots' => 'noindex, follow'], $head, $model);
        }
    }

    public function test_empty_fields_fall_back_and_canonical_follows_slug_category_and_pagination_without_tracking_queries(): void
    {
        config(['app.url' => 'https://example.com']);
        SeoSetting::create(['site_title' => 'Site', 'canonical_base' => 'https://example.com']);
        $category = LectureCategory::create(['name' => 'Kategori', 'slug' => 'seo-kategori', 'is_active' => true]);
        $test = Lecture::create(['title' => 'SEO Konusu', 'slug' => 'konu', 'lecture_category_id' => $category->id, 'summary' => 'İçerik açıklaması', 'content' => 'İçerik', 'is_published' => true]);
        $head = $this->metadataHead('/konu/seo-kategori/konu?utm_source=x');
        $this->assertStringContainsString('SEO Konusu', $head['title']);
        $this->assertSame('İçerik açıklaması', $head['description']);
        $this->assertSame('https://example.com/konu/seo-kategori/konu', $head['canonical']);
        $test->update(['slug' => 'yeni']);
        $category->update(['slug' => 'yeni-kategori']);
        $this->assertSame('https://example.com/konu/yeni-kategori/yeni', $this->metadataHead('/konu/yeni-kategori/yeni')['canonical']);
        $blogCategory = BlogCategory::create(['name' => 'Blog kategori', 'slug' => 'blog-kategori']);
        $this->get('/blog?category=blog-kategori&page=2&utm_medium=x')->assertNotFound();
        $this->assertSame('https://example.com/kategori/blog-kategori/2', $this->metadataHead('/kategori/blog-kategori?page=2&utm_medium=x')['canonical']);
        $this->get('/kategori/olmayan-kategori')->assertNotFound();
        $this->get('/blog?category=olmayan-kategori')->assertNotFound();
        $this->assertSame('https://example.com/konu', $this->metadataHead('/konu')['canonical']);
    }

    public function test_general_pagination_rule_applies_to_public_list_continuations(): void
    {
        $category = BlogCategory::create(['name' => 'Haber', 'slug' => 'haber']);
        foreach (range(1, 10) as $number) {
            BlogPost::create([
                'title' => 'Haber '.$number, 'slug' => 'haber-'.$number, 'blog_category_id' => $category->id,
                'content' => 'İçerik', 'author_name' => 'Test Yazar', 'is_published' => true, 'published_at' => now(),
            ]);
        }
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $form = Livewire::test(SeoSettings::class)->assertSet('pagination_index', 'index');
        $this->assertSame('index, follow', $this->metadataHead('/blog?page=2')['robots']);
        $form->set('pagination_index', 'noindex')->call('save')->assertHasNoErrors()->assertNoRedirect();
        Livewire::test(SeoSettings::class)->assertSet('pagination_index', 'noindex');
        foreach (['/blog' => '/blog/sayfa/2', '/kategori/haber' => '/kategori/haber/2', '/yazar/test-yazar' => '/yazar/test-yazar/2'] as $path => $secondPage) {
            $this->assertSame('index, follow', $this->metadataHead($path.'?page=1')['robots']);
            $this->get($path)->assertOk()->assertSee('href="'.$secondPage.'"', false);
            $this->get($secondPage)->assertOk()->assertSee('href="'.$path.'"', false)
                ->assertViewHas('posts', fn ($posts) => $posts->count() === 1 && $posts->first()->slug === 'haber-1');
            $head = $this->metadataHead($secondPage.'?utm_medium=test');
            $this->assertSame('noindex, follow', $head['robots']);
            $this->assertSame($secondPage, parse_url($head['canonical'], PHP_URL_PATH));
            $this->assertNull(parse_url($head['canonical'], PHP_URL_QUERY));
        }
        $this->assertSame('index, follow', $this->metadataHead('/blog/haber-1?page=2')['robots']);
        $form->set('pagination_index', 'inherit')->call('save')->assertHasErrors('pagination_index');
        $form->set('pagination_index', 'index')->call('save')->assertHasNoErrors();
        $category->update(['seo_index' => 'noindex']);
        $this->assertSame('index, follow', $this->metadataHead('/blog?page=2')['robots']);
        $this->assertSame('noindex, follow', $this->metadataHead('/kategori/haber')['robots']);
        $this->assertSame('index, follow', $this->metadataHead('/kategori/haber/2')['robots']);
        $this->assertSame('noindex, follow', $this->metadataHead('/arama?q=haber&page=2')['robots']);
    }

    public function test_custom_seo_captures_visibility_and_allows_returning_to_inheritance(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $setting = SeoSetting::create(['content_rules' => ['blog_categories' => ['index' => 'noindex']]]);
        Livewire::test(BlogCategoryForm::class)->set('name', 'Haber')
            ->set('seo_canonical', 'https://example.com/haber')->assertSet('seo_index', 'inherit')
            ->set('seo_description', 'Kategori açıklaması')->assertSet('seo_index', 'noindex')
            ->assertDontSee('Genel ayarı kullan')->call('save')->assertHasNoErrors();
        $category = BlogCategory::where('slug', 'haber')->firstOrFail();
        $setting->update(['content_rules' => ['blog_categories' => ['index' => 'index']]]);
        $this->assertSame('noindex, follow', $this->metadataHead('/kategori/haber')['robots']);
        Livewire::test(BlogCategoryForm::class, ['id' => $category->id])
            ->set('seo_index', 'inherit')->call('save')->assertHasErrors('seo_index')
            ->set('seo_index', 'index')->set('seo_title', 'Haberler')->set('seo_description', '')
            ->assertDontSee('Genel ayarı kullan')->call('save')->assertHasNoErrors()
            ->set('seo_title', '')->assertSee('Genel ayarı kullan')
            ->set('seo_index', 'inherit')->call('save')->assertHasNoErrors();
        $this->assertSame('inherit', $category->fresh()->seo_index);
    }

    public function test_migration_pins_legacy_custom_content_and_preserves_other_choices(): void
    {
        $settings = SeoSetting::create(['content_rules' => [
            'blog_categories' => ['index' => 'noindex'], 'pagination' => ['index' => 'inherit'],
        ]]);
        foreach (['title', 'description', 'blank', 'explicit'] as $slug) {
            BlogCategory::create(['name' => $slug, 'slug' => $slug]);
        }
        $rows = DB::table('blog_categories');
        (clone $rows)->where('slug', 'title')->update(['seo_title' => 'Başlık']);
        (clone $rows)->where('slug', 'description')->update(['seo_description' => 'Açıklama']);
        (clone $rows)->where('slug', 'blank')->update(['seo_description' => '  ', 'seo_canonical' => 'https://example.com/']);
        (clone $rows)->where('slug', 'explicit')->update(['seo_index' => 'index', 'seo_title' => 'Başlık']);
        $migration = require database_path('migrations/2026_11_25_200000_pin_custom_seo_visibility.php');
        $migration->up();
        $this->assertSame(['noindex', 'noindex', 'inherit', 'index'], $rows->orderBy('id')->pluck('seo_index')->all());
        $this->assertSame(['blog_categories' => ['index' => 'noindex'], 'pagination' => ['index' => 'index']], $settings->fresh()->content_rules);
    }

    public function test_static_page_and_author_settings_are_independent_and_keywords_are_removed(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->assertFalse(Schema::hasColumn('seo_settings', 'keywords'));
        Livewire::test(SeoSettings::class)->assertDontSee('wire:model="keywords"', false);
        $form = Livewire::test(SeoPages::class)->set('selectedPage', 'blog.index')->set('seo_title', 'Özel Blog')
            ->set('seo_description', 'Blog açıklaması')->set('seo_index', 'noindex')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $form->set('selectedPage', 'contact.show')->assertSet('seo_title', '')->set('seo_title', 'Özel İletişim')->call('save')->assertHasNoErrors();
        $form->set('selectedPage', 'blog.index')->assertSet('seo_title', 'Özel Blog');
        $this->assertSame('Özel Blog', $this->metadataHead('/blog')['title']);
        $this->assertSame('noindex, follow', $this->metadataHead('/blog')['robots']);
        $this->assertSame('Özel İletişim', $this->metadataHead('/iletisim')['title']);
        $category = BlogCategory::create(['name' => 'Yazılar', 'slug' => 'yazilar']);
        $post = BlogPost::create(['title' => 'Yazı', 'slug' => 'yazi', 'blog_category_id' => $category->id, 'content' => 'İçerik', 'is_published' => true, 'published_at' => now(), 'author_name' => 'Yazar Test']);
        $slug = \Illuminate\Support\Str::slug($post->fresh()->author_name);
        Livewire::test(SeoPages::class)->set('selectedPage', 'blog.author:'.$slug)->call('save')->assertHasErrors('selectedPage');
        Livewire::test(SeoSettings::class)->set('content_rules.authors.title', '%ad% Ortak Yazar SEO')->call('save')->assertHasNoErrors();
        $this->assertSame($post->fresh()->author_name.' Ortak Yazar SEO', $this->metadataHead('/yazar/'.$slug)['title']);
        $this->get('/admin/seo-settings/pages')->assertOk();
    }

    public function test_validation_rejects_unsafe_canonical_and_invalid_index_without_changing_saved_content(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $page = Page::create(['title' => 'Sayfa', 'slug' => 'seo-sayfa']);
        foreach (['javascript:alert(1)', '/goreli-adres', 'https://user:pass@example.com/', 'https://example.com/#parca'] as $canonical) {
            Livewire::test(PageForm::class, ['id' => $page->id])->set('seo_canonical', $canonical)->call('save')->assertHasErrors('seo_canonical');
            $this->assertNull($page->fresh()->seo_canonical);
        }
        Livewire::test(PageForm::class, ['id' => $page->id])->set('seo_index', 'index,follow<script>')->call('save')->assertHasErrors('seo_index');
        Livewire::test(PageForm::class, ['id' => $page->id])->set('seo_title', '"><script>alert(1)</script>')->call('save')->assertHasNoErrors();
        $this->get('/seo-sayfa')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        Livewire::test(SeoPages::class)->set('selectedPage', 'admin.users')->assertHasErrors('selectedPage');
        $this->assertDatabaseMissing('seo_route_settings', ['route_key' => 'admin.users']);
    }

    public function test_private_attempts_remain_noindex_and_drafts_and_errors_cannot_publish_metadata(): void
    {
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'seo-deneme', 'is_published' => true, 'order' => 1, 'duration_minutes' => 45, 'seo_title' => 'Deneme SEO', 'seo_index' => 'index']);
        $exam->questions()->create(['question_text' => 'Soru?', 'status' => 'Yayında', 'options' => [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki']], 'correct_option' => 'A']);
        $this->get('/deneme/seo-deneme')->assertOk()->assertSessionMissing('guest_test_attempts');
        $this->assertDatabaseCount('exam_attempts', 0);
        $guestHead = $this->metadataHead('/deneme/seo-deneme');
        $this->assertSame('noindex, nofollow', $guestHead['robots']);
        $this->assertNull($guestHead['canonical']);
        $this->actingAs(User::factory()->create());
        $this->post(route('exams.start', $exam))->assertRedirect('/deneme/seo-deneme');
        $head = $this->metadataHead('/deneme/seo-deneme');
        $this->assertSame('noindex, nofollow', $head['robots']);
        $this->assertNull($head['canonical']);
        Page::create(['title' => 'Taslak', 'slug' => 'seo-taslak', 'status' => 'draft', 'seo_title' => 'Gizli SEO']);
        $this->get('/seo-taslak')->assertNotFound()->assertDontSee('<title>Gizli SEO', false)->assertDontSee('rel="canonical"', false);
        $this->assertStringStartsWith('noindex', $this->metadataHead('/arama?q=test')['robots']);
    }
}
