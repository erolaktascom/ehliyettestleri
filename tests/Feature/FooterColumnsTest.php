<?php

namespace Tests\Feature;

use App\Livewire\Admin\{NavigationGroupForm, NavigationItems, NavigationItemForm};
use App\Models\{NavigationGroup, NavigationItem, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FooterColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_columns_can_be_created_hidden_reordered_and_deleted_with_their_own_links(): void {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('admin.navigation-items.footer.columns.create'))->assertOk()->assertSee('Sütun Başlığı');
        Livewire::test(NavigationGroupForm::class)->set('name', 'Yeni Sütun')->set('order', 5)
            ->call('save')->assertHasNoErrors()->assertRedirect(route('admin.navigation-items.footer'));
        $group = NavigationGroup::where('name', 'Yeni Sütun')->sole();
        $this->get(route('admin.navigation-items.footer.columns.edit', $group->id))->assertOk()->assertSee('Sütunu Düzenle');
        $this->get('/blog')->assertDontSee('Yeni Sütun'); // Empty columns do not leave headings/gaps.
        Livewire::test(NavigationItemForm::class, ['menu' => 'footer'])->set('name', 'Yeni Sütun Bağlantısı')
            ->set('url', '/blog')->set('footer_group', $group->key)->call('save')->assertHasNoErrors();
        $link = $group->items()->sole();
        $this->get('/blog')->assertSee('Yeni Sütun Bağlantısı');
        $list = Livewire::test(NavigationItems::class, ['menu' => 'footer']);
        $list->call('toggleGroup', $group->id)->assertHasNoErrors();
        $this->get('/blog')->assertDontSee('Yeni Sütun');
        $this->assertModelExists($link);
        $list->call('toggleGroup', $group->id)->call('moveGroup', $group->id, 'up')->assertHasNoErrors();
        $ordered = NavigationGroup::footerGroups()->pluck('key')->all();
        $this->assertLessThan(array_search('apps', $ordered), array_search($group->key, $ordered));
        $bottom = NavigationItem::where('menu', 'footer')->whereNull('footer_group')->firstOrFail();
        $store = NavigationItem::where('footer_group', 'apps')->firstOrFail();
        $list->call('requestGroupDeletion', $group->id)->assertSee('Sütunu ve Bağlantıları Sil');
        $this->assertModelExists($group);
        $list->call('cancelGroupDeletion')->assertSet('pendingDeleteGroupId', null);
        $list->call('requestGroupDeletion', $group->id)->call('deleteGroup')->assertHasNoErrors();
        $this->assertModelMissing($group); $this->assertModelMissing($link);
        $this->assertModelExists($bottom); $this->assertModelExists($store);
        $this->get('/blog')->assertDontSee('Yeni Sütun');
        Livewire::test(NavigationItemForm::class, ['menu' => 'footer'])->set('name', 'Stale Column')
            ->set('url', '/blog')->set('footer_group', $group->key)->call('save')->assertHasErrors('footer_group');
    }

    public function test_apps_have_separate_routes_and_validation_and_can_be_removed_and_recreated(): void {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $store = NavigationItem::where('footer_group', 'apps')->firstOrFail();
        $group = NavigationGroup::where('key', 'apps')->sole();
        $this->get(route('admin.navigation-items.apps.columns.edit', $group->id))->assertOk();
        $this->get(route('admin.navigation-items.apps'))->assertOk()->assertSee($store->name)->assertDontSee('Hakkımızda');
        $this->get(route('admin.navigation-items.footer'))->assertOk()->assertDontSee($store->name);
        $this->get(route('admin.navigation-items.edit', $store->id))->assertOk()->assertSee('Uygulamalar');
        $this->get(route('admin.navigation-items.footer.edit', $store->id))->assertNotFound();
        $this->get(route('admin.navigation-items.footer.columns.edit', $group->id))->assertNotFound();
        Livewire::test(NavigationItemForm::class, ['menu' => 'apps', 'id' => $store->id])
            ->set('footer_group', 'corporate')->call('save')->assertHasErrors('footer_group');
        Livewire::test(NavigationItemForm::class, ['menu' => 'footer'])
            ->set('name', 'Invalid')->set('url', '/blog')->set('footer_group', 'apps')->call('save')->assertHasErrors('footer_group');
        $list = Livewire::test(NavigationItems::class, ['menu' => 'apps']);
        $list->call('toggleGroup', $group->id);
        $this->get('/blog')->assertDontSee('class="store-badge"', false);
        $this->assertModelExists($store);
        $list->call('requestGroupDeletion', $group->id)->call('deleteGroup');
        $this->assertModelMissing($store);
        $this->get('/blog')->assertDontSee('class="store-badge"', false);
        $list->assertSee('Sütun Ekle');
        $this->get(route('admin.navigation-items.apps.columns.create'))->assertOk()->assertSee('Sütun Başlığı');
        Livewire::test(NavigationGroupForm::class, ['menu' => 'apps'])->call('save')->assertHasNoErrors();
        $this->assertSame(1, NavigationGroup::where('key', 'apps')->count());
        Livewire::test(NavigationItemForm::class, ['menu' => 'apps'])->set('name', 'App Store')->set('url', '')
            ->call('save')->assertHasNoErrors();
        $this->get('/blog')->assertSee('App Store')->assertDontSee('href=""', false);
    }

    public function test_new_forms_use_existing_menu_permission_and_cannot_cross_tabs(): void {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']));
        foreach (['apps', 'footer'] as $menu) {
            $this->get(route('admin.navigation-items.'.$menu))->assertForbidden();
            $this->get(route('admin.navigation-items.'.$menu.'.columns.create'))->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $group = NavigationGroup::where('key', 'corporate')->sole();
        try {
            Livewire::test(NavigationItems::class, ['menu' => 'apps'])->call('requestGroupDeletion', $group->id);
            $this->fail('Cross-tab group deletion must be rejected.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertModelExists($group);
        }
    }
}
