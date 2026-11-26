<?php

namespace App\Livewire\Admin;

use App\Models\AdminRole;
use App\Support\AdminAccess;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Bölüm İzinleri'])]
class AdminRoleForm extends Component
{
    #[Locked]
    public int $roleId;

    public array $permissions = [];

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdministrator(), 403);
    }

    public function mount(int $id): void
    {
        $role = AdminRole::whereIn('key', ['admin', 'editor'])->findOrFail($id);
        $this->roleId = $role->id;
        $this->permissions = $role->key === 'admin' ? array_keys(AdminAccess::modules()) : ($role->permissions ?? []);
    }

    public function selectAll(): void
    {
        $this->permissions = array_keys(AdminAccess::editorModules());
    }

    public function clearPermissions(): void
    {
        $this->permissions = [];
    }

    public function save()
    {
        $role = AdminRole::findOrFail($this->roleId);
        abort_unless($role->key === 'editor', 403);
        $this->validate([
            'permissions' => ['array'],
            'permissions.*' => ['required', 'string', 'distinct', Rule::in(array_keys(AdminAccess::editorModules()))],
        ]);
        $role->update(['permissions' => $this->permissions]);
        session()->flash('success', 'Editörün bölüm izinleri güncellendi. Değişiklikler tüm editörlere hemen uygulanır.');
    }

    public function render()
    {
        return view('livewire.admin.admin-role-form', [
            'role' => AdminRole::findOrFail($this->roleId),
            'groups' => AdminAccess::groups(),
            'editorModules' => AdminAccess::editorModules(),
        ]);
    }
}
