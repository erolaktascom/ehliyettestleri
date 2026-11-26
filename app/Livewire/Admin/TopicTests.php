<?php

namespace App\Livewire\Admin;

use App\Models\TopicCategory;
use App\Models\TopicTest;
use Livewire\Component;
use Livewire\WithPagination;

class TopicTests extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $selectedCategory = null;
    public string $selectedStatus = '';
    public string $selectedSection = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategory' => ['except' => null],
        'selectedStatus' => ['except' => ''],
        'selectedSection' => ['except' => ''],
    ];

    public function updatingSelectedSection(): void { $this->resetPage(); }
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingSelectedCategory(): void { $this->resetPage(); }
    public function updatingSelectedStatus(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $test = TopicTest::findOrFail($id);
        $test->is_published = !$test->is_published;
        $test->save();
        session()->flash('success', $test->title . ' durumu güncellendi.');
    }

    public function delete(int $id): void
    {
        TopicTest::findOrFail($id)->delete();
        session()->flash('success', 'Konu testi başarıyla silindi.');
    }

    public function render()
    {
        $query = TopicTest::with('category')->withCount(['questions', 'attempts', 'questions as published_questions_count' => fn ($query) => $query->where('questions.status', 'Yayında'), 'questions as draft_questions_count' => fn ($query) => $query->where('questions.status', 'Taslak')]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->selectedSection !== '') $query->where('section', $this->selectedSection);
        if ($this->selectedCategory) {
            $query->where('topic_category_id', $this->selectedCategory);
        }
        if ($this->selectedStatus !== '') {
            $query->where('is_published', (bool) $this->selectedStatus);
        }

        return view('livewire.admin.topic-tests', [
            'tests' => $query->orderBy('order')->paginate(15),
            'categories' => TopicCategory::orderBy('order')->get(),
        ])->layout('layouts.admin');
    }
}
