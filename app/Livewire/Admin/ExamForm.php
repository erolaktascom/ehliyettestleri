<?php

namespace App\Livewire\Admin;

use App\Models\Exam;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Locked;
use App\Livewire\Admin\Concerns\ManagesTestQuestions;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.admin')]
class ExamForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsSeo;
    use ManagesTestQuestions;
    use \App\Livewire\Admin\Concerns\EditsPublicationDate;

    #[Locked]
    public ?int $examId = null;

    // Form fields
    public string $title = '';
    public string $slug = '';
    public bool $slugManuallyEdited = false;
    public ?string $description = '';
    public int $duration_minutes = 45;
    public int $passing_score = 70;
    public int $order = 1;
    public bool $is_published = true;

    public function mount(?int $id = null): void
    {
        $this->examId = $id;

        $exam = $id ? Exam::findOrFail($id) : new Exam();
        $this->loadSeo($exam);
        if ($id) {
            $this->loadPublicationDate($exam);
            $this->loadTestQuestions($exam);
            $this->title = $exam->title;
            $this->slug = $exam->slug;
            $this->description = $exam->description;
            $this->duration_minutes = (int) $exam->duration_minutes;
            $this->passing_score = (int) $exam->passing_score;
            $this->order = (int) $exam->order;
            $this->is_published = (bool) $exam->is_published;
        } else {
            $this->loadPublicationDate();
            $this->duration_minutes = 45;
            $this->passing_score = 70;
            $this->order = (Exam::max('order') ?? 0) + 1;
            $this->is_published = true;
        }
    }

    public function updatedTitle($value): void
    {
        if (!$this->examId && !$this->slugManuallyEdited) {
            $this->slug = \App\Support\TestSlugs::generate(new Exam, (string) $value);
        }
    }

    public function updatedSlug(): void { $this->slugManuallyEdited = true; }

    public function rules(): array
    {
        return [
            ...$this->seoRules(),
            'published_at' => 'nullable|date',
            'title' => 'required|string|min:3|max:255',
            'slug' => 'required|string|max:255|unique:exams,slug,' . ($this->examId ?? 'NULL'),
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1|max:180',
            'passing_score' => 'required|integer|min:0|max:100',
            'order' => 'required|integer|min:0',
            'is_published' => 'boolean',
        ];
    }

    public function save()
    {
        if (!$this->examId && (!$this->slugManuallyEdited || trim($this->slug) === '')) {
            $this->slug = \App\Support\TestSlugs::generate(new Exam, $this->title);
        }
        $this->validateTestQuestions();
        $validated = $this->validate();
        DB::transaction(function () use ($validated) {
            $test = $this->examId ? Exam::findOrFail($this->examId) : new Exam;
            $validated['published_at'] = $this->publicationDateForSave($test);
            $test->fill($validated)->save();
            $this->syncTestQuestions($test);
        });
        session()->flash('success', 'Test ve soru sırası kaydedildi.');
        if (!$this->examId) {
            return redirect()->route('admin.exams');
        }
    }

    public function render()
    {
        return view('livewire.admin.exam-form', $this->questionPickerData())->with('creatorRecord', $this->examId ? Exam::find($this->examId) : null);
    }
}
