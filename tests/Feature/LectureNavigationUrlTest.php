<?php
namespace Tests\Feature;

use App\Models\{NavigationItem, SeoRedirect};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LectureNavigationUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_url_uses_a_deletable_redirect_and_current_page_uses_konu(): void
    {
        $this->get('/ders-notlari')->assertStatus(301)->assertRedirect('/konu');
        $this->get('/konu')->assertOk()->assertSee('Konu Anlatımı');
        SeoRedirect::where('old_url', '/ders-notlari')->delete();
        $this->get('/ders-notlari')->assertNotFound();
        $this->get('/konu')->assertOk();
    }

    public function test_navigation_migration_updates_desktop_mobile_and_footer_without_changing_customization(): void
    {
        foreach (['desktop','mobile','footer'] as $menu) {
            $item=NavigationItem::create(['name'=>'Ders Notları','url'=>'/ders-notlari','menu'=>$menu,'audience'=>'Herkes','is_active'=>true,'order'=>7]);
            $ids[]=$item->id;
        }
        $custom=NavigationItem::create(['name'=>'Özel Başlık','url'=>'/ders-notlari?ref=menu','menu'=>'desktop','audience'=>'Üyeler','is_active'=>false,'order'=>19]);
        $external=NavigationItem::create(['name'=>'Ders Notları','url'=>'https://external.example/konu','menu'=>'desktop']);
        $migration=require database_path('migrations/2026_11_02_200000_rename_lecture_navigation.php');
        $migration->up();
        foreach ($ids as $id) $this->assertDatabaseHas('navigation_items',['id'=>$id,'name'=>'Konu Anlatımı','url'=>'/konu','order'=>7]);
        $this->assertDatabaseHas('navigation_items',['id'=>$custom->id,'name'=>'Özel Başlık','url'=>'/konu?ref=menu','audience'=>'Üyeler','is_active'=>false,'order'=>19]);
        $this->assertSame('Ders Notları',$external->fresh()->name);
        $this->assertSame(1,SeoRedirect::where('old_url','/ders-notlari')->count());
        $this->get('/konu')->assertOk()->assertSee('href="/konu"',false)->assertDontSee('href="/ders-notlari"',false);
    }
}