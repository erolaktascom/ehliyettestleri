<?php

namespace App\Livewire\Admin;

use App\Models\Exam;
use Livewire\Component;
use Livewire\WithPagination;

class Exams extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedStatus' => ['except' => ''],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingSelectedStatus(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $exam = Exam::findOrFail($id);
        $exam->is_published = !$exam->is_published;
        $exam->save();
        session()->flash('success', $exam->title . ' yayım durumu güncellendi.');
    }

    public function delete(int $id): void
    {
        Exam::findOrFail($id)->delete();
        session()->flash('success', 'Sınav başarıyla silindi.');
    }

    public function render()
    {
        $query = Exam::withCount(['questions', 'attempts', 'questions as published_questions_count' => fn ($query) => $query->where('questions.status', 'Yayında'), 'questions as draft_questions_count' => fn ($query) => $query->where('questions.status', 'Taslak')]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->selectedStatus !== '') {
            $query->where('is_published', (bool) $this->selectedStatus);
        }

        return view('livewire.admin.exams', [
            'exams' => $query->orderBy('order')->paginate(15),
        ])->layout('layouts.admin');
    }
}
