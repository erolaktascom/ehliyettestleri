<?php
namespace Tests\Feature;

use App\Models\{SeoSetting, SeoRouteSetting, SiteSetting, Page, TopicCategory, TopicTest, Exam, User};
use App\Support\{PageSeo, SeoTemplates, SiteSitemap};
use App\Livewire\Admin\{SeoSettings, SeoPages};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeoCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_legacy_values_cannot_control_head_sitemap_or_robots(): void
    {
        config(['app.url'=>'https://current.example']);
        SiteSetting::create(['site_name'=>'Test Site','site_description'=>'Güncel site açıklaması']);
        SeoSetting::create(['canonical_base'=>'https://old.example','meta_description'=>'Eski gizli açıklama']);
        $this->get('/')->assertOk()->assertSee('href="https://current.example/"',false)
            ->assertSee('content="Güncel site açıklaması"',false)->assertDontSee('Eski gizli açıklama')->assertDontSee('old.example');
        $this->get('/sitemap_index.xml')->assertOk()->assertSee('https://current.example/')->assertDontSee('old.example');
        $this->get('/robots.txt')->assertOk()->assertSee('https://current.example/sitemap_index.xml')->assertDontSee('old.example');
        SeoRouteSetting::create(['route_key'=>'home','seo_canonical'=>'https://custom.example/main','seo_description'=>'Özel açıklama']);
        $this->get('/')->assertOk()->assertSee('href="https://custom.example/main"',false)->assertSee('Özel açıklama');
    }

    public function test_standalone_rules_are_removed_but_explicit_page_settings_remain(): void
    {
        $rules=SeoTemplates::values();
        $rules['pages']=$rules['static_pages']=['title'=>'Eski şablon','description'=>'Eski açıklama','index'=>'noindex'];
        SeoSetting::create(['content_rules'=>$rules]);
        Page::create(['title'=>'Bağımsız','slug'=>'bagimsiz','short_description'=>'Sayfa açıklaması']);
        $this->get('/bagimsiz')->assertOk()->assertSee('Bağımsız')->assertDontSee('Eski şablon')->assertSee('content="index, follow"',false);
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        Livewire::test(SeoSettings::class)->assertDontSee('Ana Sayfa ve Liste Sayfaları')
            ->assertDontSee('wire:model.live.debounce.300ms="content_rules.pages.title"',false)
            ->call('save')->assertHasNoErrors();
        $this->assertArrayNotHasKey('pages',SeoSetting::first()->content_rules);
        $this->assertArrayNotHasKey('static_pages',SeoSetting::first()->content_rules);
        Livewire::test(SeoPages::class)->assertSet('seo_index','index')->assertDontSee('Genel ayarı kullan')
            ->set('seo_index','noindex')->set('seo_title','Özel ana sayfa')->call('save')->assertHasNoErrors();
        auth()->logout();
        $this->get('/')->assertOk()->assertSee('<title>Özel ana sayfa</title>',false)->assertSee('content="noindex, follow"',false);
    }

    public function test_default_test_titles_are_distinct_and_preview_uses_real_content_without_invented_fallbacks(): void
    {
        SiteSetting::create(['site_name'=>'EhliyetGo']);
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        foreach(['ilk-yardim'=>'İlk Yardım','trafik'=>'Trafik'] as $slug=>$name){
            $category=TopicCategory::create(['name'=>$name,'slug'=>$slug,'is_active'=>true]);
            $test = TopicTest::create(['title'=>'Seviye 1','slug'=>'seviye-1','topic_category_id'=>$category->id,'is_published'=>true]);
            $test->questions()->create(['question_text'=>'Başlık sorusu','status'=>'Yayında','options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']],'correct_option'=>'A']);
            $this->get($test->publicUrl())->assertRedirect($test->publicUrl());
            $this->get('/konu-testleri/'.$slug.'/seviye-1')->assertOk()->assertSee('<title>'.$name.' Seviye 1 Testi | EhliyetGo</title>',false);
        }
        $exam = Exam::create(['title'=>'Seviye 1','slug'=>'seviye-1','is_published'=>true,'order'=>1]);
        $exam->questions()->attach($test->questions()->first()->id, ['position'=>1]);
        $this->get('/deneme/seviye-1')->assertRedirect('/deneme/seviye-1');
        $this->get('/deneme/seviye-1')->assertOk()->assertSee('<title>Seviye 1 Ehliyet Deneme Sınavı | EhliyetGo</title>',false);
        $preview=SeoTemplates::preview('topic_tests',SeoTemplates::values()['topic_tests'],'EhliyetGo');
        $this->assertSame('İlk Yardım Seviye 1 Testi | EhliyetGo',$preview['title']);
        $this->assertSame('Seviye 1',$preview['sample']);
        $blank=SeoTemplates::preview('topic_tests',['title'=>'','description'=>''],'EhliyetGo');
        $this->assertSame('',$blank['title']);$this->assertSame('',$blank['description']);
        $this->assertNull(SeoTemplates::preview('members',[],'EhliyetGo')['sample']);
    }

    public function test_cleanup_migration_preserves_overrides_dates_and_moves_old_rules_to_individual_pages(): void
    {
        // Recreate the legacy schema to test migration from its original input.
        \Illuminate\Support\Facades\Schema::table('seo_settings', function ($table) {
            $table->text('meta_description')->nullable();
            $table->string('canonical_base')->nullable();
        });
        SiteSetting::create(['site_name'=>'Marka']);
        $setting=SeoSetting::create(['canonical_base'=>'https://old.example','meta_description'=>'Eski ana açıklaması',
            'verification_tags'=>['google-site-verification'=>'keep'],'content_rules'=>[
                'pages'=>['title'=>'Eski %baslik%','description'=>'%ozet%','index'=>'noindex'],
                'static_pages'=>['title'=>'Sabit %baslik%','description'=>'','index'=>'noindex'],
                'exams'=>['title'=>'Özel sınav %baslik%','description'=>'','index'=>'index'],
                'topic_tests'=>['title'=>'','description'=>'Test açıklama','index'=>'noindex'],
            ]]);
        \Illuminate\Support\Facades\DB::table('seo_settings')->where('id', $setting->id)->update(['meta_description'=>'Eski ana açıklaması','canonical_base'=>'https://old.example']);
        $page=Page::create(['title'=>'Bilgi','slug'=>'bilgi','short_description'=>'Özet','seo_index'=>'inherit']);
        $dates=[$page->created_at,$page->updated_at,$page->content_updated_at];
        $special=Page::create(['title'=>'Özel','slug'=>'ozel','seo_title'=>'Korunan','seo_index'=>'index']);
        SeoRouteSetting::create(['route_key'=>'home','seo_title'=>'Özel ana','seo_index'=>'index']);
        $migration=require database_path('migrations/2026_11_02_250000_simplify_seo_rules.php');$migration->up();
        $page->refresh();$setting->refresh();
        $this->assertSame('Eski Bilgi',$page->seo_title);$this->assertSame('noindex',$page->seo_index);
        $this->assertEquals($dates,[$page->created_at,$page->updated_at,$page->content_updated_at]);
        $this->assertSame('Korunan',$special->fresh()->seo_title);$this->assertSame('index',$special->fresh()->seo_index);
        $home=SeoRouteSetting::where('route_key','home')->first();
        $this->assertSame('Özel ana',$home->seo_title);$this->assertSame('Eski ana açıklaması',$home->seo_description);$this->assertSame('index',$home->seo_index);
        $this->assertNull($setting->canonical_base);$this->assertNull($setting->meta_description);
        $this->assertArrayNotHasKey('pages',$setting->content_rules);$this->assertArrayNotHasKey('static_pages',$setting->content_rules);
        $this->assertSame('Özel sınav %baslik%',$setting->content_rules['exams']['title']);
        $this->assertSame(SeoTemplates::DEFAULT_TITLES['topic_tests'],$setting->content_rules['topic_tests']['title']);
        $this->assertSame('noindex',$setting->content_rules['topic_tests']['index']);
        $this->assertSame(['google-site-verification'=>'keep'],$setting->verification_tags);
    }
    public function test_legacy_standalone_index_save_is_not_an_edit_and_migration_handles_missing_settings(): void
    {
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $page=Page::create(['title'=>'Eski sayfa','slug'=>'eski-sayfa','content'=>'Metin']);
        \Illuminate\Support\Facades\DB::table('pages')->where('id',$page->id)->update(['seo_index'=>'inherit']);
        $auditCount=\App\Models\AuditLog::count();
        Livewire::test(\App\Livewire\Admin\PageForm::class,['id'=>$page->id])->assertSet('seo_index','index')->call('save')->assertHasNoErrors();
        $this->assertNull($page->fresh()->content_updated_at);
        $this->assertNull($page->fresh()->last_edited_by);
        $this->assertSame($auditCount,\App\Models\AuditLog::count());
        SeoSetting::query()->delete();
        $migration=require database_path('migrations/2026_11_02_250000_simplify_seo_rules.php');$migration->up();
        $this->assertSame('index',$page->fresh()->seo_index);
        $this->assertNull($page->fresh()->content_updated_at);
    }
}
