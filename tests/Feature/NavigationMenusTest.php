<?php

namespace Tests\Feature;

use App\Livewire\Admin\{NavigationItemForm, NavigationItems};
use App\Models\{NavigationItem, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class NavigationMenusTest extends TestCase
{
    use RefreshDatabase;

    private function item(string $menu, string $name, array $extra = []): NavigationItem
    {
        return NavigationItem::create(array_merge(['name' => $name, 'url' => '/'.$name, 'menu' => $menu,
            'audience' => 'Herkes', 
            'is_active' => true, 'order' => 1], $extra));
    }

    public function test_each_menu_has_separate_list_create_and_edit_routes(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (NavigationItem::ADMIN_MENUS as $menu => $title) {
            $item = $this->item(in_array($menu, ['footer-bottom', 'apps']) ? 'footer' : $menu, $menu.'-specific', ['footer_group' => match ($menu) { 'footer' => 'corporate', 'apps' => 'apps', default => null }]);
            $this->get(route('admin.navigation-items.'.$menu))->assertOk()->assertSee($title)->assertSee($item->name);
            $this->get(route('admin.navigation-items.'.$menu.'.create'))->assertOk()->assertSee($title);
            $this->get(route('admin.navigation-items.'.$menu.'.edit', $item->id))->assertOk()->assertSee($title);
        }
        $desktop = NavigationItem::where('menu', 'desktop')->first();
        $this->get(route('admin.navigation-items.mobile.edit', $desktop->id))->assertNotFound();
    }

    public function test_edits_orders_and_deletions_do_not_affect_the_other_menu(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $desktop = $this->item('desktop', 'same-link', ['order' => 3]);
        $mobile = $this->item('mobile', 'same-link', ['order' => 3]);
        Livewire::test(NavigationItemForm::class, ['id' => $mobile->id, 'menu' => 'mobile'])
            ->set('name', 'mobile-edited')->set('order', 15)->set('is_active', false)->call('save')->assertHasNoErrors()
            ->assertNoRedirect();
        $this->assertSame('same-link', $desktop->fresh()->name);
        $this->assertSame(3, $desktop->fresh()->order);
        $this->assertTrue($desktop->fresh()->is_active);
        $this->assertFalse($mobile->fresh()->is_active);
        Livewire::test(NavigationItems::class, ['menu' => 'mobile'])->call('delete', $mobile->id);
        $this->assertModelExists($desktop);
        $this->assertModelMissing($mobile);
        try {
            Livewire::test(NavigationItems::class, ['menu' => 'mobile'])->call('delete', $desktop->id);
            $this->fail('Other menu record must not be deleted.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertSame(NavigationItem::class, $e->getModel());
        }
        $this->assertModelExists($desktop);
    }

    public function test_create_uses_selected_menu_and_its_own_order_and_search_stays_scoped(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        NavigationItem::query()->delete();
        $this->item('desktop', 'desktop-only', ['order' => 100, 'url' => '/search-shared']);
        $this->item('mobile', 'mobile-only', ['order' => 2, 'url' => '/search-shared']);
        Livewire::test(NavigationItemForm::class, ['menu' => 'mobile'])->assertSet('order', 3)
            ->set('name', 'new-mobile')->set('url', '/new-mobile')->call('save')->assertHasNoErrors();
        $new = NavigationItem::where('name', 'new-mobile')->firstOrFail();
        $this->assertSame('mobile', $new->menu);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('navigation_items', 'is_mobile'));
        Livewire::test(NavigationItems::class, ['menu' => 'mobile'])->set('search', 'search-shared')
            ->assertSee('mobile-only')->assertDontSee('desktop-only');
    }

    private function navText(string $html, string $label): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        return (new \DOMXPath($dom))->query('//nav[@aria-label="'.$label.'"]')->item(0)?->textContent ?? '';
    }

    public function test_site_reads_independent_menus_and_applies_order_audience_and_status(): void
    {
        NavigationItem::query()->delete();
        $this->item('desktop', 'DESKTOP_ONLY');
        $this->item('mobile', 'MOBILE_SECOND', ['order' => 20]);
        $this->item('mobile', 'MOBILE_FIRST', ['order' => 10]);
        $this->item('mobile', 'MEMBERS_ONLY', ['audience' => 'Üyeler']);
        $this->item('mobile', 'GUESTS_ONLY', ['audience' => 'Misafirler']);
        $this->item('mobile', 'INACTIVE_LINK', ['is_active' => false]);
        $this->item('mobile', 'UNSAFE_LINK', ['url' => 'javascript:alert(1)']);
        $this->item('footer', 'FOOTER_ONLY');
        $html = $this->get('/blog')->assertOk()->getContent();
        $desktop = $this->navText($html, 'Ana menü');
        $mobile = $this->navText($html, 'Mobil menü');
        $this->assertStringContainsString('DESKTOP_ONLY', $desktop);
        $this->assertStringNotContainsString('MOBILE_FIRST', $desktop);
        $this->assertStringContainsString('MOBILE_FIRST', $mobile);
        $this->assertStringNotContainsString('DESKTOP_ONLY', $mobile);
        $this->assertLessThan(strpos($mobile, 'MOBILE_SECOND'), strpos($mobile, 'MOBILE_FIRST'));
        $this->assertStringContainsString('GUESTS_ONLY', $mobile);
        foreach (['MEMBERS_ONLY', 'INACTIVE_LINK', 'UNSAFE_LINK'] as $hidden) $this->assertStringNotContainsString($hidden, $mobile);
        $this->assertStringContainsString('FOOTER_ONLY', $this->navText($html, 'Alt bilgi menüsü'));
        $this->actingAs(User::factory()->create());
        $mobile = $this->navText($this->get('/blog')->assertOk()->getContent(), 'Mobil menü');
        $this->assertStringContainsString('MEMBERS_ONLY', $mobile);
        $this->assertStringNotContainsString('GUESTS_ONLY', $mobile);
        NavigationItem::where('menu', 'mobile')->delete();
        $this->assertSame('', trim($this->navText($this->get('/blog')->getContent(), 'Mobil menü')));
    }

    public function test_form_rejects_unsafe_links_and_unknown_audiences(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['javascript:alert(1)', '//evil.test', '/%2fevil.test', '/bad%0aurl', 'data:text/html,test'] as $url) {
            Livewire::test(NavigationItemForm::class, ['menu' => 'mobile'])->set('name', 'Invalid')->set('url', $url)
                ->call('save')->assertHasErrors('url');
        }
        Livewire::test(NavigationItemForm::class)->set('name', 'Invalid')->set('url', '/valid')->set('audience', 'anything')
            ->call('save')->assertHasErrors('audience');
    }

    public function test_all_menu_routes_require_the_existing_menu_permission(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']));
        foreach (array_keys(NavigationItem::ADMIN_MENUS) as $menu) {
            $item = $this->item($menu === 'footer-bottom' ? 'footer' : $menu, 'permission-'.$menu, ['footer_group' => $menu === 'footer' ? 'corporate' : null]);
            $this->get(route('admin.navigation-items.'.$menu))->assertForbidden();
            $this->get(route('admin.navigation-items.'.$menu.'.create'))->assertForbidden();
            $this->get(route('admin.navigation-items.'.$menu.'.edit', $item->id))->assertForbidden();
        }
    }

    public function test_migration_splits_shared_rows_preserving_values_and_hidden_items(): void
    {
        NavigationItem::query()->delete();
        $migration = require database_path('migrations/2026_10_29_070000_separate_navigation_menus.php');
        $migration->down();
        \Illuminate\Support\Facades\Schema::table('navigation_items', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->boolean('is_web')->default(true);
            $table->boolean('is_mobile')->default(true);
            $table->boolean('is_footer')->default(false);
        });
        $id = DB::table('navigation_items')->insertGetId(['name' => 'Original', 'url' => '/original', 'audience' => 'Herkes',
            'is_web' => true, 'is_mobile' => true, 'is_footer' => true, 'is_active' => true, 'order' => 9]);
        DB::table('navigation_items')->insert(['name' => 'Hidden', 'url' => '/hidden', 'is_web' => false, 'is_mobile' => false, 'is_footer' => false]);
        $migration->up();
        $this->assertSame('desktop', NavigationItem::findOrFail($id)->menu);
        $copies = NavigationItem::where('name', 'Original')->get();
        $this->assertCount(3, $copies);
        $this->assertEqualsCanonicalizing(['desktop', 'mobile', 'footer'], $copies->pluck('menu')->all());
        foreach ($copies as $copy) {
            $this->assertSame('/original', $copy->url);
            $this->assertSame(9, $copy->order);
            $this->assertSame(1, (int) $copy->is_web + (int) $copy->is_mobile + (int) $copy->is_footer);
        }
        $this->assertFalse(NavigationItem::where('name', 'Hidden')->firstOrFail()->is_active);
    }
}
