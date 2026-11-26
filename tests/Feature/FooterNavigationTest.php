<?php

namespace Tests\Feature;

use App\Livewire\Admin\{NavigationItems, NavigationItemForm, NavigationGroupForm};
use App\Models\{NavigationGroup, NavigationItem, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FooterNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function footer(string $html): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        return (new \DOMXPath($dom))->query('//nav[@aria-label="Alt bilgi sütunları"]')->item(0)?->textContent ?? '';
    }

    public function test_headings_save_in_place_and_immediately_change_the_site(): void
    {
        $this->admin();
        foreach (['curriculum' => 'Derslerimiz', 'corporate' => 'Bize Ulaşın', 'apps' => 'Mobil Uygulamalar'] as $key => $name) {
            $group = NavigationGroup::where('key', $key)->firstOrFail();
            $form = Livewire::test(NavigationGroupForm::class, ['id' => $group->id, 'menu' => $key === 'apps' ? 'apps' : 'footer'])
                ->set('name', $name)->call('save')->assertHasNoErrors()->assertNoRedirect();
        }
        $footer = $this->footer($this->get('/blog')->assertOk()->getContent());
        foreach (['Derslerimiz', 'Bize Ulaşın', 'Mobil Uygulamalar'] as $name) $this->assertStringContainsString($name, $footer);
        $form->set('name', 'Yeni Başlık')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertSame(3, NavigationGroup::count());
        $this->assertDatabaseHas('audit_logs', ['record_type' => 'Alt Bilgi Başlığı', 'changed_fields' => 'Düzenlendi: Ad']);
        $this->assertStringContainsString('Yeni Başlık', $this->footer($this->get('/blog')->getContent()));
        $form->set('name', '')->call('save')->assertHasErrors('name');
    }

    public function test_footer_link_crud_group_order_visibility_and_safe_urls(): void
    {
        $this->admin();
        Livewire::test(NavigationItemForm::class, ['menu' => 'footer'])
            ->set('name', 'Yeni Bağlantı')->set('url', 'https://example.com/app')->set('footer_group', 'curriculum')
            ->set('order', 0)->call('save')->assertHasNoErrors()->assertRedirect(route('admin.navigation-items.footer'));
        $link = NavigationItem::where('name', 'Yeni Bağlantı')->firstOrFail();
        $this->assertSame('curriculum', $link->footer_group);
        $this->get('/blog')->assertSee('https://example.com/app');
        $form = Livewire::test(NavigationItemForm::class, ['id' => $link->id, 'menu' => 'footer'])
            ->assertSet('footer_group', 'curriculum')->set('footer_group', 'corporate')->set('name', 'İlk Kurumsal Bağlantı')
            ->set('audience', 'Üyeler')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $footer = $this->footer($this->get('/blog')->getContent());
        $this->assertLessThan(strpos($footer, 'Hakkımızda'), strpos($footer, 'İlk Kurumsal Bağlantı'));
        auth()->logout();
        $this->assertStringNotContainsString('İlk Kurumsal Bağlantı', $this->footer($this->get('/blog')->getContent()));
        $this->admin();
        $form->set('is_active', false)->call('save')->assertHasNoErrors();
        $this->assertStringNotContainsString('İlk Kurumsal Bağlantı', $this->footer($this->get('/blog')->getContent()));
        $form->set('url', 'javascript:alert(1)')->call('save')->assertHasErrors('url');
        Livewire::test(NavigationItems::class, ['menu' => 'footer'])->call('delete', $link->id);
        $this->assertModelMissing($link);
        $this->assertStringNotContainsString('İlk Kurumsal Bağlantı', $this->footer($this->get('/blog')->getContent()));
    }

    public function test_store_placeholders_render_without_links_until_an_address_is_added(): void
    {
        $this->assertSame(3, NavigationGroup::count());
        $this->assertSame(10, NavigationItem::where('menu', 'footer')->whereNotNull('footer_group')->count());
        $this->assertSame(2, NavigationItem::where('footer_group', 'apps')->where('is_active', true)->count());
        $footer = $this->footer($this->get('/blog')->assertOk()->getContent());
        $this->assertStringContainsString('Trafik ve Çevre Bilgisi', $footer);
        $this->assertStringContainsString('İletişim', $footer);
        $this->assertStringContainsString('Play Store', $footer);
        $this->assertStringContainsString('App Store', $footer);
        $store = NavigationItem::where('footer_group', 'apps')->where('name', 'Play Store')->firstOrFail();
        $this->get('/blog')->assertDontSee('href=""', false);
        $this->admin();
        $form = Livewire::test(NavigationItemForm::class, ['menu' => 'apps', 'id' => $store->id])
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $form->set('url', 'https://play.google.com/store/apps/details?id=example.app')
            ->call('save')->assertHasNoErrors();
        $this->get('/blog')->assertSee('href="https://play.google.com/store/apps/details?id=example.app"', false);
        $form->set('url', '')->call('save')->assertHasNoErrors();
        $this->get('/blog')->assertDontSee('play.google.com')->assertSee('Play Store');
        $form->set('is_active', false)->call('save')->assertHasNoErrors();
        $this->assertStringNotContainsString('Play Store', $this->footer($this->get('/blog')->getContent()));
    }

    public function test_group_selection_cannot_modify_other_menus_or_use_unknown_groups(): void
    {
        $this->admin();
        foreach (['desktop', 'mobile', 'footer'] as $menu) {
            Livewire::test(NavigationItemForm::class, ['menu' => $menu])->set('name', 'Bad Group')
                ->set('url', '/blog')->set('footer_group', $menu === 'footer' ? 'unknown' : 'apps')
                ->call('save')->assertHasErrors('footer_group');
        }
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']));
        $this->get(route('admin.navigation-items.footer'))->assertForbidden();
        $this->get(route('admin.navigation-items.footer.create'))->assertForbidden();
    }

    public function test_bottom_menu_can_be_edited_reordered_hidden_and_emptied_without_fallbacks(): void
    {
        $this->admin();
        $bottom = function () {
            $html = $this->get('/blog')->assertOk()->getContent();
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
            return (new \DOMXPath($dom))->query('//nav[@aria-label="Alt bilgi menüsü"]')->item(0)?->textContent ?? '';
        };
        $this->assertStringContainsString('Gizlilik Politikası', $bottom());
        $this->assertStringContainsString('Kullanım Koşulları', $bottom());
        $this->assertSame(2, substr_count($bottom(), '•'));
        $privacy = NavigationItem::where('menu', 'footer')->whereNull('footer_group')->where('url', '/gizlilik-politikasi')->firstOrFail();
        $form = Livewire::test(NavigationItemForm::class, ['id' => $privacy->id, 'menu' => 'footer-bottom'])
            ->assertSee('Alt Bağlantılar')->set('name', 'Yeni Gizlilik')->set('url', '/yeni-gizlilik')
            ->set('order', 9)->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->get('/blog')->assertSee('href="/yeni-gizlilik"', false);
        $this->assertLessThan(strpos($bottom(), 'Yeni Gizlilik'), strpos($bottom(), 'Kullanım Koşulları'));
        $form->set('audience', 'Üyeler')->call('save')->assertHasNoErrors();
        auth()->logout();
        $this->assertStringNotContainsString('Yeni Gizlilik', $bottom());
        $this->assertSame(1, substr_count($bottom(), '•'));
        $this->admin();
        $form->set('is_active', false)->call('save')->assertHasNoErrors();
        $this->assertStringNotContainsString('Yeni Gizlilik', $bottom());
        $list = Livewire::test(NavigationItems::class, ['menu' => 'footer-bottom']);
        foreach (NavigationItem::where('menu', 'footer')->whereNull('footer_group')->pluck('id') as $id) {
            $list->call('delete', $id)->assertHasNoErrors();
        }
        $this->assertSame('', $bottom());
        $this->assertSame('', $bottom()); // A new request must not recreate the defaults.
    }

    public function test_bottom_menu_migration_removes_only_the_extra_blog_and_does_not_duplicate_links(): void
    {
        $attributes = ['name' => 'Blog', 'url' => '/blog', 'audience' => 'Herkes', 'is_active' => true];
        $desktop = NavigationItem::create($attributes + ['menu' => 'desktop']);
        $extra = NavigationItem::create($attributes + ['menu' => 'footer']);
        $column = NavigationItem::create($attributes + ['menu' => 'footer', 'footer_group' => 'corporate']);
        // Restore the URL shape at the time of the historical migration.
        foreach (['gizlilik-politikasi', 'kullanim-kosullari'] as $slug) {
            NavigationItem::where('url', '/'.$slug)->update(['url' => '/sayfa/'.$slug]);
        }
        $migration = require database_path('migrations/2026_10_29_150000_manage_footer_bottom_links.php');
        $migration->up();
        $migration->up();
        $this->assertModelMissing($extra);
        $this->assertModelExists($desktop);
        $this->assertModelExists($column);
        $this->assertSame(3, NavigationItem::where('menu', 'footer')->whereNull('footer_group')->count());
    }

    public function test_footer_tabs_scope_lists_search_forms_and_deletion(): void
    {
        $this->admin();
        $column = NavigationItem::create(['menu' => 'footer', 'footer_group' => 'corporate', 'name' => 'COLUMN_UNIQUE', 'url' => '/shared-search']);
        $bottom = NavigationItem::create(['menu' => 'footer', 'name' => 'BOTTOM_UNIQUE', 'url' => '/shared-search']);
        foreach (['footer' => [$column, $bottom], 'footer-bottom' => [$bottom, $column]] as $menu => [$own, $other]) {
            Livewire::test(NavigationItems::class, ['menu' => $menu])->set('search', 'shared-search')
                ->assertSee($own->name)->assertDontSee($other->name);
            $this->get(route('admin.navigation-items.'.$menu.'.edit', $other->id))->assertNotFound();
            try {
                Livewire::test(NavigationItems::class, ['menu' => $menu])->call('delete', $other->id);
                $this->fail('Another tab record must not be deleted.');
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                $this->assertModelExists($other);
            }
        }
        $this->get(route('admin.navigation-items.footer-bottom'))->assertOk()->assertDontSee('Başlıkları Kaydet');
        $this->get(route('admin.navigation-items.edit', $bottom->id))->assertOk()->assertSee('Alt Bağlantılar');
    }

    public function test_bottom_creation_and_footer_group_validation_keep_tabs_separate(): void
    {
        $this->admin();
        $form = Livewire::test(NavigationItemForm::class, ['menu' => 'footer-bottom'])
            ->assertDontSee('Alt Bilgi Konumu')->set('name', 'Yeni Alt Bağlantı')->set('url', '/yeni-alt');
        $form->set('footer_group', 'apps')->call('save')->assertHasErrors('footer_group');
        $form->set('footer_group', null)->call('save')->assertHasNoErrors()
            ->assertRedirect(route('admin.navigation-items.footer-bottom'));
        $link = NavigationItem::where('name', 'Yeni Alt Bağlantı')->firstOrFail();
        $this->assertSame('footer', $link->menu);
        $this->assertNull($link->footer_group);
        $this->assertSame('footer', $link->menu);
        Livewire::test(NavigationItemForm::class, ['menu' => 'footer'])
            ->set('name', 'Sütun Bağlantısı')->set('url', '/sutun')->set('footer_group', null)
            ->call('save')->assertHasErrors('footer_group');
    }
}
