<?php

namespace App\Livewire\Admin;

use App\Models\Question;
use App\Models\TopicCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class QuestionForm extends Component
{
    use \App\Livewire\Admin\Concerns\ResolvesEditorVideo;
    use \App\Livewire\Admin\Concerns\UploadsEditorImages;
    use \App\Livewire\Admin\Concerns\EditsPublicationDate;
    public ?int $questionId = null;

    // Form fields
    public ?int $topic_category_id = null;
    public string $question_text = '';

    // Dynamic options array: [['key' => 'A', 'text' => '...'], ...]
    public array $options = [];
    public string $correct_option = 'A';

    public string $type = 'Çoktan Seçmeli';
    public string $status = 'Yayında';
    public ?string $explanation = '';

    public function mount(?int $id = null): void
    {
        $this->questionId = $id;

        if ($this->questionId) {
            $q = Question::findOrFail($this->questionId);
            $this->loadPublicationDate($q);
            $this->topic_category_id = $q->topic_category_id;
            $this->question_text = \App\Support\QuestionMedia::clean($q->question_text);
            $this->correct_option = strtoupper($q->correct_option ?? 'A');
            $this->type = $q->type ?? 'Çoktan Seçmeli';
            $this->status = $q->status ?? 'Yayında';
            $this->explanation = $q->explanation;

            $this->options = [];
            foreach ($q->options_list as $key => $text) {
                $this->options[] = ['key' => $key, 'text' => $text];
            }

        } else {
            $this->loadPublicationDate();
            $this->status = 'Yayında';
            $this->correct_option = 'A';

            // Default 4 options
            $this->options = [
                ['key' => 'A', 'text' => ''],
                ['key' => 'B', 'text' => ''],
                ['key' => 'C', 'text' => ''],
                ['key' => 'D', 'text' => ''],
            ];
        }
    }

    /**
     * Dynamically add a new option (e.g. E, F, etc.)
     */
    public function addOption(): void
    {
        if (count($this->options) >= 8) {
            return; // Maximum 8 options
        }

        $nextKey = chr(65 + count($this->options));
        $this->options[] = [
            'key' => $nextKey,
            'text' => '',
        ];
    }

    /**
     * Dynamically remove an option by its index
     */
    public function removeOption(int $index): void
    {
        if ($index < 0 || !array_key_exists($index, $this->options)) {
            return;
        }
        if (count($this->options) <= 2) {
            session()->flash('warning', 'Bir soruda en az 2 şık bulunmalıdır.');
            return;
        }

        $correctIndex = array_search($this->correct_option, array_column($this->options, 'key'), true);
        array_splice($this->options, $index, 1);

        // Re-index keys sequentially (A, B, C...)
        foreach ($this->options as $i => &$opt) {
            $opt['key'] = chr(65 + $i);
        }

        // Keep the same answer text selected when letters shift after removal.
        $this->correct_option = $correctIndex === false || $correctIndex === $index
            ? ''
            : chr(65 + ($correctIndex > $index ? $correctIndex - 1 : $correctIndex));
    }

    public function rules(): array
    {
        $validKeys = implode(',', array_column($this->options, 'key'));

        return [
            'published_at' => 'nullable|date',
            'topic_category_id' => 'nullable|exists:topic_categories,id',
            'question_text' => 'required|string|min:3',
            'options' => 'required|array|min:2|max:8',
            'options.*.key' => 'required|string|in:A,B,C,D,E,F,G,H|distinct',
            'options.*.text' => 'required|string|min:1',
            'correct_option' => 'required|in:' . ($validKeys ?: 'A,B'),
            'type' => 'required|string',
            'status' => 'required|in:Yayında,Taslak',
            'explanation' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'question_text.required' => 'Lütfen soru metnini yazın.',
            'options.min' => 'Soru için en az 2 şık eklenmelidir.',
            'options.*.text.required' => 'Şık metni boş bırakılamaz.',
            'correct_option.required' => 'Lütfen doğru cevabı belirleyin.',
            'correct_option.in' => 'Seçilen doğru cevap mevcut şıklardan biri olmalıdır.',
            'status.in' => 'Yayın durumu Yayında veya Taslak olmalıdır.',
        ];
    }

    public function save()
    {
        $this->validate();

        if ($this->questionId && Question::findOrFail($this->questionId)->topicTests()
            ->where('topic_category_id', '!=', $this->topic_category_id ?? 0)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'topic_category_id' => 'Bu soru farklı kategorideki bir konu testinde kullanılıyor. Kategorisini değiştirmeden önce ilgili testten çıkarın.',
            ]);
        }

        $data = [
            'topic_category_id' => $this->topic_category_id ?: null,
            'question_text' => \App\Support\QuestionMedia::clean($this->question_text),
            'options' => $this->options,
            'correct_option' => $this->correct_option,
            'type' => $this->type,
            'status' => $this->status,
            'explanation' => $this->explanation ?: null,
        ];

        if ($this->questionId) {
            $question = Question::findOrFail($this->questionId);
            $data['published_at'] = $this->publicationDateForSave($question);
            $question->update($data);
            session()->flash('success', 'Soru başarıyla güncellendi.');
        } else {
            $data['published_at'] = $this->published_at;
            Question::create($data);
            session()->flash('success', 'Yeni soru başarıyla eklendi.');
        }

        if (!$this->questionId) {
            return redirect()->route('admin.questions');
        }
    }

    public function render()
    {
        $categories = TopicCategory::where('is_active', true)->orWhere('id', $this->topic_category_id)->orderBy('order')->get();

        return view('livewire.admin.question-form', compact('categories'))->with('creatorRecord', $this->questionId ? Question::find($this->questionId) : null);
    }
}
