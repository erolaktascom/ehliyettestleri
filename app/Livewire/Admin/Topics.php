<?php

namespace App\Livewire\Admin;

use App\Models\Lecture;
use App\Models\LectureCategory;
use Livewire\Component;
use Livewire\WithPagination;

class Topics extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterCategory = '';
    public string $filterStatus = '';

    protected $queryString = ['search' => ['except' => ''], 'filterCategory' => ['except' => ''], 'filterStatus' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterCategory() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $lecture = Lecture::findOrFail($id);
            $lecture->update(['is_published' => !$lecture->is_published]);
        });
        session()->flash('success', 'Konu anlatımının yayın durumu güncellendi.');
    }

    public function delete(int $id)
    {
        $lecture = Lecture::findOrFail($id);
        $lecture->delete();
        session()->flash('success', 'Konu anlatımı silindi.');
    }

    public function render()
    {
        $query = Lecture::with('category')->orderBy('order');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%')
                  ->orWhere('summary', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterCategory) {
            $query->where('lecture_category_id', $this->filterCategory);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_published', (bool) $this->filterStatus);
        }

        return view('livewire.admin.topics', [
            'topics' => $query->paginate(15),
            'categories' => LectureCategory::orderBy('order')->get(),
        ])->layout('layouts.admin');
    }
}
