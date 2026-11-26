<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class StaffUsers extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        if (auth()->id() === $id) {
            session()->flash('error', 'Kendi yönetici hesabınızı silemezsiniz!');
            return;
        }

        User::where('is_admin', true)->findOrFail($id)->delete();
        session()->flash('success', 'Yönetici silindi.');
    }

    public function render()
    {
        $query = User::where('is_admin', true);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        $staff = $query->latest()->paginate(15);

        return view('livewire.admin.staff-users', [
            'staff' => $staff,
        ])->layout('layouts.admin', ['title' => 'Yönetim Ekibi']);
    }
}
