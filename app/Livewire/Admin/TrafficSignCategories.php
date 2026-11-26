<?php

namespace App\Livewire\Admin;

use App\Models\TrafficSignCategory;
use Livewire\Component;
use Livewire\WithPagination;

class TrafficSignCategories extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        TrafficSignCategory::findOrFail($id)->delete();
        session()->flash('success', 'İşaret kategorisi silindi.');
    }

    public function render()
    {
        $query = TrafficSignCategory::withCount('signs');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%');
        }

        $categories = $query->orderBy('order')->paginate(15);

        return view('livewire.admin.traffic-sign-categories', [
            'categories' => $categories,
        ])->layout('layouts.admin', ['title' => 'İşaret Kategorileri']);
    }
}
