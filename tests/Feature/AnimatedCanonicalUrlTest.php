<?php
namespace Tests\Feature;
use App\Models\{NavigationItem,SeoRedirect,SeoRouteSetting};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AnimatedCanonicalUrlTest extends TestCase {
    use RefreshDatabase;
    public function test_one_public_page_with_a_deletable_managed_redirect(): void
    {
        $this->get('/animasyonlu-sorular')->assertOk()->assertViewIs('pages.animated-questions.index');
        $this->get('/animasyonlu-ehliyet-sorulari?ref=test')->assertStatus(301)->assertRedirect('/animasyonlu-sorular?ref=test');
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('animated-questions.alias'));
        $this->get('/sabit-sayfalar-sitemap.xml')->assertOk()->assertSee('/animasyonlu-sorular')->assertDontSee('/animasyonlu-ehliyet-sorulari');
        SeoRedirect::where('old_url','/animasyonlu-ehliyet-sorulari')->delete();
        $this->get('/animasyonlu-ehliyet-sorulari')->assertNotFound();
    }
    public function test_navigation_moves_without_changing_settings_or_external_links(): void
    {
        config(['app.url'=>'https://example.com']);
        $menu=NavigationItem::create(['name'=>'Özel Menü','url'=>'/animasyonlu-ehliyet-sorulari?ref=footer#liste','menu'=>'footer','order'=>9,'is_active'=>false]);
        $absolute=NavigationItem::create(['name'=>'Mobil','url'=>'https://example.com/animasyonlu-ehliyet-sorulari','menu'=>'mobile']);
        $outside=NavigationItem::create(['name'=>'Dış','url'=>'https://outside.example/animasyonlu-ehliyet-sorulari','menu'=>'desktop']);
        $setting=SeoRouteSetting::updateOrCreate(['route_key'=>'animated-questions.index'],['seo_title'=>'Korunan Başlık','seo_description'=>'Korunan açıklama','seo_index'=>'noindex','seo_canonical'=>'https://example.com/animasyonlu-ehliyet-sorulari']);
        $before=$menu->fresh()->getAttributes();
        $migration=require database_path('migrations/2026_11_02_270000_unify_animated_questions_url.php');$migration->up();
        $expected=$before;$expected['url']='/animasyonlu-sorular?ref=footer#liste';
        $this->assertSame($expected,$menu->fresh()->getAttributes());
        $this->assertSame('https://example.com/animasyonlu-sorular',$absolute->fresh()->url);
        $this->assertSame('https://outside.example/animasyonlu-ehliyet-sorulari',$outside->fresh()->url);
        $this->assertNull($setting->fresh()->seo_canonical);
        $this->assertSame('Korunan Başlık',$setting->fresh()->seo_title);
        $this->assertSame('noindex',$setting->fresh()->seo_index);
        $this->get('/animasyonlu-sorular')->assertOk()->assertSee('href="https://example.com/animasyonlu-sorular"',false)->assertSee('Korunan Başlık');
        $setting->update(['seo_canonical'=>'https://outside.example/other']);$migration->up();
        $this->assertSame('https://outside.example/other',$setting->fresh()->seo_canonical);
        $this->assertSame(1,SeoRedirect::where('old_url','/animasyonlu-ehliyet-sorulari')->count());
    }
}