<?php
namespace Tests\Feature;

use App\Models\{SeoSetting, SeoRouteSetting, Page, BlogPost, TopicCategory, TopicTest, User, AdminRole, SeoRedirect};
use App\Livewire\Admin\{SitemapSettings, SeoSettings};
use App\Support\{SiteSitemap, SeoTemplates, AdminAccess};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    private function locations(string $path, string $root = 'urlset'): array
    {
        $response = $this->get($path)->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $doc = simplexml_load_string($response->getContent());
        $this->assertNotFalse($doc);
        $this->assertSame($root, $doc->getName());
        $doc->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        return array_map(fn ($node) => (string) $node, $doc->xpath('//sm:loc'));
    }

    public function test_index_is_split_into_content_categories_and_static_sections_with_real_public_urls(): void
    {
        config(['app.url' => 'http://localhost']);
        $this->seed(DatabaseSeeder::class);
        SeoSetting::first()->update(['canonical_base' => 'http://localhost', 'sitemap_enabled' => true]);
        $index = $this->locations('/sitemap_index.xml', 'sitemapindex');
        $playSections = ['deneme-sinavlari', 'konu-testleri', 'animasyonlu-testler'];
        $this->assertCount(count(SiteSitemap::SECTIONS) - count($playSections), $index);
        $this->assertSame($index, $this->locations('/sitemap.xml', 'sitemapindex'));
        $all = [];
        foreach (SiteSitemap::SECTIONS as $section => $config) {
            if (in_array($section, $playSections, true)) {
                $this->assertNotContains('http://localhost'.SiteSitemap::path($section), $index);
                $this->assertSame([], iterator_to_array((new SiteSitemap)->entries($section)));
                $this->get(SiteSitemap::path($section))->assertNotFound();
                continue;
            }
            $this->assertContains('http://localhost'.SiteSitemap::path($section), $index);
            $urls = $this->locations(SiteSitemap::path($section));
            $this->assertNotEmpty($urls);
            foreach ($urls as $url) {
                $this->assertStringNotContainsString('/oturum/', $url);
                $this->assertStringNotContainsString('/admin', $url);
                $response = $this->get($url);
                $this->assertSame(200, $response->status(), $url.' -> '.$response->headers->get('Location'));
            }
            array_push($all, ...$urls);
        }
        $this->assertCount(count(array_unique($all)), $all);
    }

    public function test_selection_save_reopen_empty_selection_and_disable_take_effect_immediately(): void
    {
        config(['app.url' => 'http://localhost']);
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $form = Livewire::test(SitemapSettings::class)->set('sitemap_sections', ['sayfalar'])->set('sitemap_links_per_file', 2)
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        Livewire::test(SitemapSettings::class)->assertSet('sitemap_sections', ['sayfalar'])->assertSet('sitemap_links_per_file', 2);
        $index = $this->locations('/sitemap_index.xml', 'sitemapindex');
        $this->assertNotEmpty($index);
        foreach ($index as $url) $this->assertStringContainsString('/sayfalar-sitemap', $url);
        $this->get('/deneme-sinavlari-sitemap.xml')->assertNotFound();
        $form->set('sitemap_sections', [])->call('save')->assertHasNoErrors();
        $this->assertSame([], $this->locations('/sitemap_index.xml', 'sitemapindex'));
        $this->get('/sayfalar-sitemap.xml')->assertNotFound();
        $form->set('sitemap_enabled', false)->call('save')->assertHasNoErrors();
        $this->get('/sitemap_index.xml')->assertNotFound();
        $this->get('/sitemap.xml')->assertNotFound();
        $this->get('/robots.txt')->assertOk()->assertDontSee('Sitemap:');
    }

    public function test_noindex_inheritance_canonical_redirect_and_draft_exclusions_follow_actual_public_rules(): void
    {
        DB::table('pages')->delete();
        $rules = SeoTemplates::values(); $rules['pages']['index'] = 'noindex';
        config(['app.url' => 'https://example.com']);
        $setting = SeoSetting::create(['canonical_base' => 'https://example.com', 'content_rules' => $rules]);
        $index = Page::create(['title' => 'Dahil', 'slug' => 'dahil', 'seo_index' => 'index']);
        $inherited = Page::create(['title' => 'Devralan', 'slug' => 'devralan']);
        Page::create(['title' => 'Gizli', 'slug' => 'gizli', 'seo_index' => 'noindex']);
        Page::create(['title' => 'Taslak', 'slug' => 'taslak', 'status' => 'draft', 'seo_index' => 'index']);
        Page::create(['title' => 'Kopya', 'slug' => 'kopya', 'seo_index' => 'index', 'seo_canonical' => 'https://elsewhere.example.com/asil']);
        Page::create(['title' => 'Yönlenmiş', 'slug' => 'yonlenmis', 'seo_index' => 'index']);
        SeoRedirect::create(['old_url' => '/yonlenmis', 'new_url' => '/dahil', 'type' => '301', 'status' => 'Aktif']);
        // Removed page rules no longer hide standalone pages.
        $this->assertSame(['https://example.com/dahil', 'https://example.com/devralan'], $this->locations('/sayfalar-sitemap.xml'));
        $rules['pages']['index'] = 'index'; $setting->update(['content_rules' => $rules]);
        $this->assertSame(['https://example.com/dahil', 'https://example.com/devralan'], $this->locations('/sayfalar-sitemap.xml'));
        $rules['blog_posts']['index'] = 'noindex'; $setting->update(['content_rules' => $rules]);
        $post = BlogPost::create(['title' => 'Devralan yazı', 'slug' => 'devralan-yazi', 'content' => 'Metin', 'is_published' => true]);
        $this->get('/blog-yazilari-sitemap.xml')->assertNotFound();
        $rules['blog_posts']['index'] = 'index'; $setting->update(['content_rules' => $rules]);
        $this->assertSame(['https://example.com/blog/devralan-yazi'], $this->locations('/blog-yazilari-sitemap.xml'));
        $category = TopicCategory::create(['name' => 'Kapalı', 'slug' => 'kapali', 'is_active' => false]);
        TopicTest::create(['title' => 'Test', 'slug' => 'test', 'topic_category_id' => $category->id, 'is_published' => true]);
        $this->get('/konu-testleri-sitemap.xml')->assertNotFound();
        $post->delete();
        BlogPost::create(['title' => 'Sonra', 'slug' => 'sonra', 'content' => 'Yazı', 'is_published' => true, 'published_at' => now()->addDay()]);
        $this->get('/blog-yazilari-sitemap.xml')->assertNotFound();
        SeoRouteSetting::create(['route_key' => 'home', 'seo_index' => 'noindex']);
        $this->assertNotContains('https://example.com/', $this->locations('/sabit-sayfalar-sitemap.xml'));
        $this->assertSame(0, (int) SeoRedirect::first()->usage_count);
    }

    public function test_pagination_xml_escaping_slug_changes_and_content_dates(): void
    {
        DB::table('pages')->delete();
        config(['app.url' => 'https://example.com']);
        SeoSetting::create(['canonical_base' => 'https://example.com', 'sitemap_sections' => ['sayfalar'], 'sitemap_links_per_file' => 2]);
        for ($i = 1; $i <= 5; $i++) Page::create(['title' => 'Sayfa '.$i, 'slug' => 'sayfa-'.$i]);
        $index = $this->locations('/sitemap_index.xml', 'sitemapindex');
        $this->assertSame(['https://example.com/sayfalar-sitemap.xml', 'https://example.com/sayfalar-sitemap-2.xml', 'https://example.com/sayfalar-sitemap-3.xml'], $index);
        $this->assertCount(2, $this->locations('/sayfalar-sitemap.xml'));
        $this->assertCount(2, $this->locations('/sayfalar-sitemap-2.xml'));
        $this->assertCount(1, $this->locations('/sayfalar-sitemap-3.xml'));
        $this->get('/sayfalar-sitemap-4.xml')->assertNotFound();
        $this->get('/sayfalar-sitemap-0.xml')->assertNotFound();
        $this->get('/bilinmeyen-sitemap.xml')->assertNotFound();
        DB::table('pages')->where('slug', 'sayfa-1')->update(['content_updated_at' => '2026-01-03 10:00:00', 'updated_at' => '2026-02-02 10:00:00']);
        $xml = $this->get('/sayfalar-sitemap.xml')->getContent();
        $this->assertStringContainsString('2026-01-03T10:00:00', $xml);
        $this->assertStringNotContainsString('2026-02-02', $xml);
        Page::first()->update(['slug' => 'yeni-adres']);
        $this->assertContains('https://example.com/yeni-adres', $this->locations('/sayfalar-sitemap.xml'));
        $this->assertNotContains('https://example.com/sayfa-1', $this->locations('/sayfalar-sitemap.xml'));
        // The XML writer must safely escape URL query characters.
        $controller = new \App\Http\Controllers\SitemapController();
        $method = new \ReflectionMethod($controller, 'xml');
        $escaped = $method->invoke($controller, 'urlset', 'url', [['loc' => 'https://example.com/?a=1&b=2']])->getContent();
        $this->assertStringContainsString('?a=1&amp;b=2', $escaped);
        $this->assertNotFalse(simplexml_load_string($escaped));
    }

    public function test_separate_forms_preserve_other_settings_and_robots_uses_one_current_index(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        config(['app.url' => 'https://example.com']);
        SeoSetting::create(['canonical_base' => 'https://example.com', 'verification_tags' => ['google-site-verification' => 'keep'], 'robots_txt' => "User-agent: *\nDisallow: /ozel/\nSitemap: https://old.test/sitemap.xml"]);
        $general = Livewire::test(SeoSettings::class)->assertDontSee('wire:model="site_title"', false)
            ->assertDontSee('wire:model="sitemap_enabled"', false)->assertDontSee('wire:model="robots_txt"', false);
        Livewire::test(SitemapSettings::class)->set('sitemap_sections', ['sayfalar'])->call('save')->assertHasNoErrors();
        $general->set('content_rules.blog_posts.description', 'Genel açıklama')->call('save')->assertHasNoErrors();
        $this->assertSame(['sayfalar'], SeoSetting::first()->sitemap_sections);
        $this->assertSame(['google-site-verification' => 'keep'], SeoSetting::first()->verification_tags);
        $body = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString('Disallow: /ozel/', $body);
        $this->assertSame(1, substr_count($body, 'Sitemap:'));
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap_index.xml', $body);
        $this->assertStringNotContainsString('old.test', $body);
        Livewire::test(SitemapSettings::class)->set('sitemap_sections', ['admin'])->call('save')->assertHasErrors('sitemap_sections.0');
        Livewire::test(SitemapSettings::class)->set('sitemap_links_per_file', 1001)->call('save')->assertHasErrors('sitemap_links_per_file');
    }

    public function test_new_module_is_in_permissions_but_closed_to_editor_by_default(): void
    {
        $this->assertArrayHasKey('sitemap-settings', AdminAccess::modules());
        $this->assertNotContains('sitemap-settings', AdminAccess::defaultEditorPermissions());
        $this->get('/admin/sitemap-settings')->assertRedirect(route('admin.login'));
        $editor = User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']);
        $this->actingAs($editor)->get('/admin/sitemap-settings')->assertForbidden();
        AdminRole::where('key', 'editor')->firstOrFail()->update(['permissions' => ['sitemap-settings']]);
        $this->get('/admin/sitemap-settings')->assertOk()->assertSee('Dahil Edilecek Bölümler');
        $this->get('/admin/seo-settings')->assertForbidden();
    }

    public function test_migration_preserves_explicit_home_title_and_moves_legacy_title_only_into_empty_home(): void
    {
        $migration = require database_path('migrations/2026_11_02_170000_add_sitemap_settings.php');
        \Illuminate\Support\Facades\Schema::table('seo_settings', fn ($table) => $table->string('site_title')->nullable());
        \Illuminate\Support\Facades\DB::table('seo_settings')->insert(['site_title' => 'Eski Site Başlığı']);
        $migration->down(); $migration->up();
        $this->assertSame('Eski Site Başlığı', SeoRouteSetting::where('route_key', 'home')->first()->seo_title);
        SeoRouteSetting::where('route_key', 'home')->update(['seo_title' => 'Özel Ana Sayfa']);
        $migration->down(); $migration->up();
        $this->assertSame('Özel Ana Sayfa', SeoRouteSetting::where('route_key', 'home')->first()->seo_title);
    }
}
