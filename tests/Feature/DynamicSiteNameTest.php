<?php
namespace Tests\Feature;

use App\Models\{SiteSetting, SeoRouteSetting, Page, User, BlogPost, Integration};
use App\Services\CampaignMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DynamicSiteNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_changes_reach_public_titles_copy_profiles_errors_and_smtp_without_rewriting_content(): void
    {
        $site = SiteSetting::create(['site_name' => 'Birinci Marka']);
        SeoRouteSetting::create(['route_key' => 'home', 'seo_title' => '%site_adi% | Hazırlık', 'seo_description' => '%site_adi% açıklaması']);
        $page = Page::create(['title' => 'Hakkımızda', 'slug' => 'hakkimizda', 'short_description' => '%site_adi% hakkında', 'content' => '<p>%site_adi% ile öğren.</p>', 'status' => 'published']);
        $post = BlogPost::create(['title' => 'Örnek Yazı', 'slug' => 'ornek-yazi', 'content' => '<p>İçerik</p>', 'is_published' => true]);
        $member = User::factory()->create(['name' => 'Örnek Üye', 'is_admin' => false]);
        Integration::where('integration_key', 'smtp')->firstOrFail()->update(['is_active' => true, 'configuration' => [
            'host' => 'smtp.example.test', 'port' => '587', 'username' => 'test', 'password' => 'test-secret',
            'encryption' => 'tls', 'from_address' => 'sender@example.test', 'from_name' => 'Eski Marka',
        ]]);
        $mailer = app(CampaignMailer::class);
        foreach (['Birinci Marka', 'İkinci Marka'] as $name) {
            $site->update(['site_name' => $name]);
            $this->get('/blog')->assertOk()->assertSee($name.' Blog')->assertDontSee('EhliyetGo Blog');
            $this->get('/')->assertOk()->assertSee('<title>'.$name.' | Hazırlık</title>', false)->assertSee('content="'.$name.' açıklaması"', false);
            $this->get('/hakkimizda')->assertOk()->assertSee($name.' hakkında')->assertSee('<p>'.$name.' ile öğren.</p>', false);
            $this->get('/blog/'.$post->slug)->assertOk()->assertSee('<title>Örnek Yazı - '.$name.' Blog</title>', false);
            $this->get('/uye/'.$member->profile_slug)->assertOk()->assertSee($name.' profili.');
            $this->get('/brand-missing-page')->assertNotFound()->assertSee('Sayfa Bulunamadı - '.$name);
            $this->view('errors.410')->assertSee('Sayfa Kaldırıldı - '.$name);
            $this->view('pages.auth.google-link', ['email' => 'test@example.test'])->assertSee('mevcut '.$name.' şifrenizi');
            $this->assertSame($name, $mailer->settings()['from_name']);
            $this->assertSame('sender@example.test', $mailer->settings()['from_address']);
        }
        $this->actingAs($member)->get('/premium')->assertOk()->assertSee('İkinci Marka Premium sayesinde');
        $this->assertSame('<p>%site_adi% ile öğren.</p>', $page->fresh()->content);
    }

    public function test_site_name_is_escaped_in_html_content_and_custom_seo_remains_explicit(): void
    {
        SiteSetting::create(['site_name' => '<img src=x onerror=alert(1)> & Marka']);
        Page::create(['title' => 'Hakkımızda', 'slug' => 'hakkimizda', 'content' => '<p>%site_adi%</p>', 'status' => 'published']);
        $this->get('/hakkimizda')->assertOk()->assertSee('<p>&lt;img src=x onerror=alert(1)&gt; &amp; Marka</p>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
        SeoRouteSetting::create(['route_key' => 'blog.index', 'seo_title' => 'Özel Başlık', 'seo_description' => 'Özel açıklama']);
        $this->get('/blog')->assertOk()->assertSee('<title>Özel Başlık</title>', false)->assertSee('content="Özel açıklama"', false);
    }

    public function test_brand_migration_only_converts_shipped_text_and_preserves_dates_and_custom_copy(): void
    {
        $home = SeoRouteSetting::create(['route_key' => 'home', 'seo_title' => 'EhliyetGo - 2026 Ehliyet Sınav Soruları ve Denemeleri']);
        $about = Page::create(['title' => 'Hakkımızda', 'slug' => 'hakkimizda', 'short_description' => 'EhliyetGo vizyon ve misyonu',
            'content' => '<p>EhliyetGo, sürücü adaylarına modern dijital hazırlık imkanı sunar.</p>', 'status' => 'published']);
        $custom = Page::create(['title' => 'Özel', 'slug' => 'ozel', 'content' => '<p>EhliyetGo özel metin https://ehliyetgo.com</p>', 'status' => 'published']);
        $before = DB::table('pages')->where('id', $about->id)->first();
        $migration = require database_path('migrations/2026_11_02_280000_use_dynamic_site_brand.php');
        $migration->up();
        $migration->up();
        $this->assertSame('%site_adi% - 2026 Ehliyet Sınav Soruları ve Denemeleri', $home->fresh()->seo_title);
        $this->assertSame('%site_adi% vizyon ve misyonu', $about->fresh()->short_description);
        $this->assertStringStartsWith('<p>%site_adi%,', $about->fresh()->content);
        $after = DB::table('pages')->where('id', $about->id)->first();
        foreach (['updated_at','first_published_at','content_updated_at','last_edited_by'] as $field) $this->assertSame($before->$field, $after->$field);
        $this->assertSame($custom->content, $custom->fresh()->content);
        $home->update(['seo_title' => 'Özel ana sayfa']);
        $migration->up();
        $this->assertSame('Özel ana sayfa', $home->fresh()->seo_title);
    }
}
