<?php

namespace App\Livewire\Admin;

use App\Models\LectureCategory;
use Livewire\Component;
use Livewire\WithPagination;

class ContentCategories extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        $cat = LectureCategory::findOrFail($id);
        $cat->delete();
        session()->flash('success', 'Konu kategorisi silindi.');
    }

    public function render()
    {
        $query = LectureCategory::withCount('lectures')->orderBy('order');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%');
        }

        return view('livewire.admin.content-categories', [
            'categories' => $query->paginate(15),
        ])->layout('layouts.admin');
    }
}
