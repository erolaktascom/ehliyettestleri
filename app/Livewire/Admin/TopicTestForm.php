<?php

namespace App\Livewire\Admin;

use App\Models\TopicCategory;
use App\Models\TopicTest;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Locked;
use App\Livewire\Admin\Concerns\ManagesTestQuestions;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.admin')]
class TopicTestForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsSeo;
    use ManagesTestQuestions;
    use \App\Livewire\Admin\Concerns\EditsPublicationDate;

    #[Locked]
    public ?int $testId = null;

    // Form fields
    public ?int $topic_category_id = null;
    public string $title = '';
    public string $section = 'topic_tests';
    public string $slug = '';
    public bool $slugManuallyEdited = false;
    public ?string $description = '';
    public int $order = 1;
    public bool $is_published = true;

    public function mount(?int $id = null): void
    {
        $this->testId = $id;

        $test = $id ? TopicTest::findOrFail($id) : new TopicTest();
        $this->loadSeo($test);
        if ($id) {
            $this->loadPublicationDate($test);
            $this->topic_category_id = $test->topic_category_id;
            $this->loadTestQuestions($test);
            $this->title = $test->title;
            $this->section = $test->section;
            $this->slug = $test->slug;
            $this->description = $test->description;
            $this->order = (int) $test->order;
            $this->is_published = (bool) $test->is_published;
        } else {
            $this->loadPublicationDate();
            $this->topic_category_id = TopicCategory::where('is_active', true)->orderBy('order')->first()?->id;
            $this->order = (TopicTest::max('order') ?? 0) + 1;
            $this->is_published = true;
        }
    }

    public function updatedTitle($value): void
    {
        if (!$this->testId && !$this->slugManuallyEdited) {
            $this->slug = \App\Support\TestSlugs::generate(new TopicTest(['section' => $this->section, 'topic_category_id' => $this->topic_category_id]), (string) $value);
        }
    }

    public function updatedTopicCategoryId(): void { $this->updatedTitle($this->title); }
    public function updatedSection(): void { $this->updatedTitle($this->title); }

    public function updatedSlug(): void { $this->slugManuallyEdited = true; }

    public function rules(): array
    {
        return [
            ...$this->seoRules(),
            'published_at' => 'nullable|date',
            'topic_category_id' => 'required|exists:topic_categories,id',
            'title' => 'required|string|max:255',
            'section' => ['required', \Illuminate\Validation\Rule::in(array_keys(TopicTest::SECTIONS))],
            'slug' => ['required','string','max:255', \App\Support\TestSlugs::topicRule($this->section, $this->topic_category_id, $this->testId)],
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_published' => 'boolean',
        ];
    }

    public function save()
    {
        if (!$this->testId && (!$this->slugManuallyEdited || trim($this->slug) === '')) {
            $this->slug = \App\Support\TestSlugs::generate(new TopicTest(['section' => $this->section, 'topic_category_id' => $this->topic_category_id]), $this->title);
        }
        $this->validateTestQuestions();
        $validated = $this->validate();
        DB::transaction(function () use ($validated) {
            $test = $this->testId ? TopicTest::findOrFail($this->testId) : new TopicTest;
            $validated['published_at'] = $this->publicationDateForSave($test);
            $test->fill($validated)->save();
            $this->syncTestQuestions($test);
        });
        session()->flash('success', 'Test ve soru sırası kaydedildi.');
        if (!$this->testId) {
            return redirect()->route('admin.topic-tests');
        }
    }

    public function render()
    {
        $categories = TopicCategory::where('is_active', true)->orWhere('id', $this->topic_category_id)->orderBy('order')->get();

        return view('livewire.admin.topic-test-form', array_merge(compact('categories'), $this->questionPickerData()))->with('creatorRecord', $this->testId ? TopicTest::find($this->testId) : null);
    }
}
