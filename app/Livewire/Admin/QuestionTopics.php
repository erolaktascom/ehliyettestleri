<?php

namespace App\Livewire\Admin;

use App\Models\TopicCategory;
use Livewire\Component;
use Livewire\WithPagination;

class QuestionTopics extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterActive = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterActive() { $this->resetPage(); }

    public function delete(int $id)
    {
        $topic = TopicCategory::findOrFail($id);
        if ($topic->questions()->exists() || $topic->topicTests()->exists()) {
            session()->flash('error', 'Bu kategoriye bağlı test veya soru var. Önce bunları başka kategoriye taşıyın ya da kategoriyi pasife alın.');
            return;
        }
        $topic->delete();
        session()->flash('success', 'Soru kategorisi silindi.');
    }

    public function render()
    {
        $query = TopicCategory::withCount('questions');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterActive !== '') {
            $query->where('is_active', (bool) $this->filterActive);
        }

        return view('livewire.admin.question-topics', [
            'topics' => $query->orderBy('order')->paginate(15),
        ])->layout('layouts.admin');
    }
}
