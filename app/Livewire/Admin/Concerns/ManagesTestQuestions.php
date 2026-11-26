<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\Question;
use App\Models\TopicCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait ManagesTestQuestions
{
    public array $questionIds = [];
    public string $questionSearch = '';
    public ?int $questionCategoryFilter = null;
    public int $questionLimit = 20;

    protected function loadTestQuestions(Model $test): void
    {
        $this->questionIds = $test->questions()->pluck('questions.id')->map(fn ($id) => (int) $id)->all();
    }

    protected function questionCategory(): ?int
    {
        return property_exists($this, 'topic_category_id') ? $this->topic_category_id : $this->questionCategoryFilter;
    }

    public function updatedQuestionSearch(): void { $this->questionLimit = 20; }
    public function updatedQuestionCategoryFilter(): void { $this->questionLimit = 20; }
    public function moreQuestions(): void { $this->questionLimit = min(200, $this->questionLimit + 20); }

    public function addQuestion(int $id): void
    {
        $question = Question::findOrFail($id);
        if (property_exists($this, 'topic_category_id') && $question->topic_category_id !== $this->topic_category_id) {
            $this->addError('questionIds', 'Konu testine aynı kategorideki soruları ekleyin.');
            return;
        }
        if (!in_array($id, $this->questionIds, true)) $this->questionIds[] = $id;
        $this->resetValidation('questionIds');
    }

    public function removeQuestion(int $id): void
    {
        $this->questionIds = array_values(array_filter($this->questionIds, fn ($value) => (int) $value !== $id));
    }

    public function moveQuestion(int $id, int $beforeId): void
    {
        $from = array_search($id, $this->questionIds, true);
        $to = array_search($beforeId, $this->questionIds, true);
        if ($from === false || $to === false || $from === $to) return;
        array_splice($this->questionIds, $from, 1);
        array_splice($this->questionIds, $to, 0, [$id]);
    }

    public function moveQuestionBy(int $id, int $direction): void
    {
        if (!in_array($direction, [-1, 1], true)) return;
        $from = array_search($id, $this->questionIds, true);
        if ($from !== false && isset($this->questionIds[$from + $direction])) {
            $this->moveQuestion($id, $this->questionIds[$from + $direction]);
        }
    }

    protected function validateTestQuestions(): void
    {
        $this->validate([
            'questionIds' => 'array|max:100',
            'questionIds.*' => 'required|integer|distinct|exists:questions,id',
        ]);
        $selected = Question::whereIn('id', $this->questionIds)->get();
        if (property_exists($this, 'topic_category_id') && $selected->contains(fn ($question) => $question->topic_category_id !== $this->topic_category_id)) {
            throw ValidationException::withMessages(['questionIds' => 'Seçili sorular test kategorisiyle aynı kategoride olmalıdır. Kategoriyi değiştirdiyseniz uygun soruları seçin.']);
        }
    }

    protected function syncTestQuestions(Model $test): void
    {
        $positions = [];
        foreach (array_values($this->questionIds) as $position => $id) $positions[(int) $id] = ['position' => $position + 1];
        $previous = $test->questions()->get()->mapWithKeys(fn ($question) => [
            $question->id => ['position' => (int) $question->pivot->position],
        ])->all();
        $test->questions()->sync($positions);
        if ($previous != $positions) \App\Support\PanelAudit::record($test, 'Düzenlendi', ['soru listesi / sırası']);
        if (!$test->wasRecentlyCreated && !$test->wasChanged('first_published_at')
            && $previous != $positions) {
            $test->recordContentEdit();
            $test->saveQuietly();
        }
    }

    protected function questionPickerData(): array
    {
        $selected = Question::with('topicCategory')->whereIn('id', $this->questionIds)->get()->keyBy('id');
        $query = Question::with('topicCategory')->whereNotIn('id', $this->questionIds)
            ->when($this->questionCategory(), fn ($query, $id) => $query->where('topic_category_id', $id))
            ->when($this->questionSearch !== '', function ($query) {
                $query->where(fn ($search) => $search->where('question_text', 'like', '%'.mb_substr($this->questionSearch, 0, 200).'%')
                    ->orWhere('id', ctype_digit($this->questionSearch) ? (int) $this->questionSearch : -1));
            });
        return [
            'selectedQuestions' => collect($this->questionIds)->map(fn ($id) => $selected->get($id))->filter()->values(),
            'availableQuestions' => $query->orderByDesc('id')->limit(max(20, min(200, $this->questionLimit)))->get(),
            'availableCount' => (clone $query)->count(),
            'questionCategories' => TopicCategory::orderBy('order')->get(),
            'fixedQuestionCategory' => property_exists($this, 'topic_category_id'),
        ];
    }
}
