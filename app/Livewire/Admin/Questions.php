<?php

namespace App\Livewire\Admin;

use App\Models\Question;
use App\Models\TopicCategory;
use Livewire\Component;
use Livewire\WithPagination;

class Questions extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $selectedTopic = null;
    public string $selectedStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedTopic' => ['except' => null],
        'selectedStatus' => ['except' => ''],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingSelectedTopic(): void { $this->resetPage(); }
    public function updatingSelectedStatus(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $question = Question::findOrFail($id);
        $question->update(['status' => $question->status === 'Yayında' ? 'Taslak' : 'Yayında']);
        session()->flash('success', 'Sorunun yayın durumu güncellendi.');
    }

    public function delete(int $id): void
    {
        Question::findOrFail($id)->delete();
        session()->flash('success', 'Soru başarıyla silindi.');
    }

    public function render()
    {
        $query = Question::with('topicCategory');

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('question_text', 'like', '%' . $search . '%');
                if (preg_match('/^#?([0-9]+)$/', $search, $matches)) {
                    $query->orWhere('id', $matches[1]);
                }
            });
        }

        if ($this->selectedTopic) {
            $query->where('topic_category_id', $this->selectedTopic);
        }
        if ($this->selectedStatus !== '') {
            $query->where('status', $this->selectedStatus);
        }

        return view('livewire.admin.questions', [
            'questions' => $query->latest('id')->paginate(15),
            'topics' => TopicCategory::orderBy('name')->get(),
        ])->layout('layouts.admin');
    }
}
