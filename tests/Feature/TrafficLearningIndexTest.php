<?php
namespace Tests\Feature;
use App\Models\{NavigationItem,SeoRedirect,SeoRouteSetting,TrafficSignCategory};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrafficLearningIndexTest extends TestCase
{
    use RefreshDatabase;
    public function test_main_url_directly_lists_categories_and_old_redirect_is_deletable(): void
    {
        $category=TrafficSignCategory::create(['name'=>'İşaret kategorisi','slug'=>'isaret-kategorisi']);
        $this->get('/trafik-isaretleri')->assertOk()->assertViewIs('pages.traffic-signs.categories')->assertSee('İşaret kategorisi')
            ->assertSee(route('traffic-signs.learn',$category),false)->assertDontSee('Öğrenmeye Başla');
        $this->get('/ogren/trafik-isaretleri')->assertStatus(301)->assertRedirect('/trafik-isaretleri');
        SeoRedirect::where('old_url','/ogren/trafik-isaretleri')->delete();
        $this->get('/ogren/trafik-isaretleri')->assertNotFound();
        $this->get(route('traffic-signs.learn',$category))->assertOk()->assertSee('href="'.route('traffic-signs.index').'"',false);
        $this->assertArrayNotHasKey('traffic-signs.categories',\App\Support\SeoPageCatalog::PAGES);
    }
    public function test_navigation_and_seo_migrate_without_overwriting_main_page_settings(): void
    {
        $menu=NavigationItem::create(['name'=>'Özel Başlık','url'=>'/ogren/trafik-isaretleri?ref=footer','menu'=>'footer','order'=>9]);
        $outside=NavigationItem::create(['name'=>'Dış','url'=>'https://external.example/ogren/trafik-isaretleri','menu'=>'desktop']);
        SeoRouteSetting::create(['route_key'=>'traffic-signs.categories','seo_title'=>'Öğrenme başlığı','seo_description'=>'Eski açıklama','seo_index'=>'noindex']);
        $main=SeoRouteSetting::create(['route_key'=>'traffic-signs.index','seo_title'=>'Özel ana başlık','seo_index'=>'index']);
        $migration=require database_path('migrations/2026_11_02_230000_move_traffic_learning_index.php');
        $migration->up();
        $this->assertSame('/trafik-isaretleri?ref=footer',$menu->fresh()->url);
        $this->assertSame('Özel Başlık',$menu->fresh()->name);
        $this->assertSame('https://external.example/ogren/trafik-isaretleri',$outside->fresh()->url);
        $this->assertSame('Özel ana başlık',$main->fresh()->seo_title);
        $this->assertSame('Eski açıklama',$main->fresh()->seo_description);
        $this->assertSame('index',$main->fresh()->seo_index);
        $this->assertDatabaseMissing('seo_route_settings',['route_key'=>'traffic-signs.categories']);
    }
}