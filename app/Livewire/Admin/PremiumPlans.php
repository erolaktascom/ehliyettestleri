<?php

namespace App\Livewire\Admin;

use App\Models\PremiumPlan;
use Livewire\Component;
use Livewire\WithPagination;

class PremiumPlans extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        PremiumPlan::findOrFail($id)->delete();
        session()->flash('success', 'Premium paketi silindi.');
    }

    public function render()
    {
        $query = PremiumPlan::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('short_code', 'like', '%' . $this->search . '%');
        }

        $plans = $query->orderBy('order')->paginate(15);

        return view('livewire.admin.premium-plans', [
            'plans' => $plans,
        ])->layout('layouts.admin', ['title' => 'Premium Paketleri']);
    }
}
