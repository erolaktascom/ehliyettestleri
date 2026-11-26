<?php
namespace Tests\Feature;

use App\Livewire\Admin\{Users, StaffUsers};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberListSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_list_and_search_exclude_staff_and_staff_list_keeps_them(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Yönetici Hesabı']);
        $editor = User::factory()->create(['is_admin' => true, 'role_name' => 'Editör', 'name' => 'Editör Hesabı']);
        $member = User::factory()->create(['name' => 'Üye Hesabı']);
        $this->actingAs($admin);
        $form = Livewire::test(Users::class)->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$member->id])
            ->assertDontSee('wire:model.live="roleFilter"', false)->assertDontSee('wire:click="toggleAdmin', false);
        foreach ([$admin->name, $editor->email] as $search) {
            $form->set('search', $search)->assertViewHas('users', fn ($users) => $users->isEmpty());
        }
        $form->set('search', $member->email)->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$member->id]);
        $this->get('/admin/staff-users')->assertOk()->assertSee($editor->email)->assertDontSee($member->email);
    }

    public function test_member_list_cannot_delete_staff_but_can_delete_a_member(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $editor = User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']);
        $member = User::factory()->create();
        $this->actingAs($admin);
        try {
            Livewire::test(Users::class)->call('delete', $editor->id);
            $this->fail('Staff account deletion must be rejected.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertSame(User::class, $exception->getModel());
        }
        $this->assertDatabaseHas('users', ['id' => $editor->id, 'is_admin' => true]);
        Livewire::test(Users::class)->call('delete', $member->id)->assertHasNoErrors();
        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }
}