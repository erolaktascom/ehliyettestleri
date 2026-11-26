<?php

namespace App\Livewire\Admin;

use App\Models\AdminRole;
use App\Support\AdminAccess;
use Livewire\Component;

class AdminRoles extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdministrator(), 403);
    }

    public function render()
    {
        return view('livewire.admin.admin-roles', [
            'roles' => AdminRole::whereIn('key', ['admin', 'editor'])->orderBy('key')->get(),
            'moduleCount' => count(AdminAccess::editorModules()),
        ])->layout('layouts.admin', ['title' => 'Rol ve İzinler']);
    }
}
