<?php

namespace App\Livewire\Admin;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use Livewire\Component;
use Livewire\WithPagination;

class TrafficSigns extends Component
{
    use WithPagination;

    public string $search = '';
    public string $publication = '';
    public ?int $selectedCategory = null;

    protected $queryString = [
        'publication' => ['except' => ''],
        'search' => ['except' => ''],
        'selectedCategory' => ['except' => null],
    ];

    public function updatingPublication(): void { $this->resetPage(); }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingSelectedCategory(): void { $this->resetPage(); }

    public function delete(int $id): void
    {
        TrafficSign::findOrFail($id)->delete();
        session()->flash('success', 'Trafik işareti silindi.');
    }

    public function render()
    {
        $query = TrafficSign::with('category');
        if (in_array($this->publication, ['0', '1'], true)) $query->where('is_published', $this->publication === '1');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%')
                  ->orWhere('meaning', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->selectedCategory) {
            $query->where('traffic_sign_category_id', $this->selectedCategory);
        }

        return view('livewire.admin.traffic-signs', [
            'signs' => $query->orderBy('order')->paginate(20),
            'categories' => TrafficSignCategory::orderBy('order')->get(),
        ])->layout('layouts.admin');
    }
}
