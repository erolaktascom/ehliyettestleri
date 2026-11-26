<?php

namespace App\Livewire\Admin;

use App\Models\AutomationRule;
use Livewire\Component;
use Livewire\WithPagination;

class AutomationRules extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        AutomationRule::findOrFail($id)->delete();
        session()->flash('success', 'Otomasyon kuralı silindi.');
    }

    public function render()
    {
        $query = AutomationRule::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('channels', 'like', '%' . $this->search . '%');
        }

        $rules = $query->latest()->paginate(15);

        return view('livewire.admin.automation-rules', [
            'rules' => $rules,
        ])->layout('layouts.admin', ['title' => 'Otomasyon Kuralları']);
    }
}
