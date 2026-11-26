<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public string $search = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdministrator(), 403);
    }

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id): void
    {
        // Staff accounts belong to the separate management-users section.
        User::where('is_admin', false)->findOrFail($id)->delete();
        session()->flash('success', 'Kullanıcı başarıyla silindi.');
    }

    public function render()
    {
        $query = User::where('is_admin', false)->withCount('attempts');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.admin.users', [
            'users' => $query->latest('id')->paginate(15),
        ])->layout('layouts.admin', ['title' => 'Üyeler']);
    }
}