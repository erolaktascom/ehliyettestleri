<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminRoleForm;
use App\Livewire\Admin\StaffUserForm;
use App\Livewire\Admin\UserForm;
use App\Models\AdminRole;
use App\Models\Page;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->create(['is_admin' => true, 'role_name' => 'Yönetici']);
    }

    private function editor(): User
    {
        return User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']);
    }

    public function test_only_two_fixed_panel_roles_exist(): void
    {
        $this->assertSame(['admin' => 'Yönetici', 'editor' => 'Editör'], AdminRole::orderBy('key')->pluck('name', 'key')->all());
        $this->actingAs($this->administrator())->get('/admin/admin-roles')->assertOk()->assertSee('Editör İzinlerini Düzenle');
        $this->get('/admin/admin-roles/create')->assertNotFound();
        $this->expectException(ValidationException::class);
        AdminRole::create(['key' => 'moderator', 'name' => 'Moderatör']);
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $this->expectException(ValidationException::class);
        AdminRole::where('key', 'editor')->firstOrFail()->delete();
    }

    public function test_saved_permissions_control_routes_and_navigation_immediately(): void
    {
        $role = AdminRole::where('key', 'editor')->firstOrFail();
        $editor = $this->editor();
        $this->assertTrue($editor->canAccessAdminRoute('admin.questions'));
        $this->actingAs($this->administrator());
        Livewire::test(AdminRoleForm::class, ['id' => $role->id])
            ->set('permissions', ['reports', 'blog-posts'])->call('save')->assertHasNoErrors()
            ->assertNoRedirect();
        $this->assertSame(2, count($role->fresh()->permissions));
        $this->assertFalse($editor->canAccessAdminRoute('admin.questions'));
        $this->actingAs($editor)->get('/admin/reports')->assertOk()
            ->assertSee(route('admin.blog-posts'), false)->assertDontSee(route('admin.questions'), false);
        $this->get('/admin/questions')->assertForbidden();
        $this->get('/admin/questions/create')->assertForbidden();
        $this->get('/admin/questions/1/edit')->assertForbidden();
        $this->get('/admin')->assertRedirect(route('admin.blog-posts'));
    }

    public function test_revoked_module_cannot_replay_a_livewire_mutation(): void
    {
        $page = Page::create(['title' => 'Korunan sayfa', 'slug' => 'korunan-sayfa', 'content' => 'İçerik']);
        $response = $this->actingAs($this->editor())->get('/admin/pages')->assertOk();
        preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $snapshot = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        AdminRole::where('key', 'editor')->firstOrFail()->update(['permissions' => ['blog-posts']]);
        $this->postJson('/livewire/update', ['components' => [[
            'snapshot' => $snapshot, 'updates' => [],
            'calls' => [['path' => '', 'method' => 'delete', 'params' => [$page->id]]],
        ]]], ['X-Livewire' => 'true'])->assertForbidden();
        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }

    public function test_editor_cannot_change_permissions_or_manage_accounts(): void
    {
        $role = AdminRole::where('key', 'editor')->firstOrFail();
        $this->actingAs($this->editor());
        $this->get('/admin/admin-roles/'.$role->id.'/edit')->assertForbidden();
        Livewire::test(AdminRoleForm::class, ['id' => $role->id])->assertForbidden();
        Livewire::test(StaffUserForm::class)->assertForbidden();
        Livewire::test(UserForm::class)->assertForbidden();
    }

    public function test_unknown_and_account_management_permissions_are_rejected(): void
    {
        $role = AdminRole::where('key', 'editor')->firstOrFail();
        $original = $role->permissions;
        $this->actingAs($this->administrator());
        foreach (['admin-roles', 'staff-users', 'users', 'unknown'] as $module) {
            Livewire::test(AdminRoleForm::class, ['id' => $role->id])
                ->set('permissions', [$module])->call('save')->assertHasErrors(['permissions.0']);
        }
        $this->assertSame($original, $role->fresh()->permissions);
    }

    public function test_select_all_and_clear_allow_safe_zero_permission_landing(): void
    {
        $role = AdminRole::where('key', 'editor')->firstOrFail();
        $this->actingAs($this->administrator());
        Livewire::test(AdminRoleForm::class, ['id' => $role->id])->call('selectAll')
            ->assertSet('permissions', array_keys(AdminAccess::editorModules()))
            ->call('clearPermissions')->assertSet('permissions', [])
            ->call('save')->assertHasNoErrors();
        $this->actingAs($this->editor())->get('/admin')->assertRedirect(route('admin.access'));
        $this->get('/admin/access')->assertOk()->assertDontSee(route('admin.blog-posts'), false);
        $this->get('/admin/blog-posts')->assertForbidden();
    }

    public function test_administrator_permissions_are_fixed(): void
    {
        $admin = $this->administrator();
        $role = AdminRole::where('key', 'admin')->firstOrFail();
        $this->actingAs($admin);
        Livewire::test(AdminRoleForm::class, ['id' => $role->id])
            ->set('permissions', [])->call('save')->assertForbidden();
        $this->assertTrue($admin->canAccessAdminRoute('admin.settings'));
    }

    public function test_account_forms_preserve_editor_role_and_reject_other_roles(): void
    {
        $editor = $this->editor();
        $this->actingAs($this->administrator());
        Livewire::test(UserForm::class, ['id' => $editor->id])->assertSet('role_name', 'Editör')
            ->set('name', 'Editör Yeni')->call('save')->assertHasNoErrors();
        $this->assertSame('Editör', $editor->fresh()->role_name);
        foreach ([StaffUserForm::class, UserForm::class] as $form) {
            Livewire::test($form, ['id' => $editor->id])->set('role_name', 'Moderatör')
                ->call('save')->assertHasErrors(['role_name']);
        }
        Livewire::test(StaffUserForm::class, ['id' => $editor->id])->set('role_name', 'Yönetici')
            ->call('save')->assertHasNoErrors();
        $this->assertTrue($editor->fresh()->isAdministrator());
    }

    public function test_administrator_cannot_demote_own_account_to_editor(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);
        foreach ([StaffUserForm::class, UserForm::class] as $form) {
            Livewire::test($form, ['id' => $admin->id])->set('role_name', 'Editör')
                ->call('save')->assertHasErrors(['role_name']);
        }
        $this->assertTrue($admin->fresh()->isAdministrator());
    }
}
