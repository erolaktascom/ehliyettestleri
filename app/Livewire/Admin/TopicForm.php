<?php

namespace App\Livewire\Admin;

use App\Models\Lecture;
use App\Models\LectureCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class TopicForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsSeo;
    use \App\Livewire\Admin\Concerns\ResolvesEditorVideo;
    use \App\Livewire\Admin\Concerns\UploadsEditorImages;
    use \App\Livewire\Admin\Concerns\EditsPublicationDate;
    public ?int $topicId = null;

    // Form fields
    public ?int $lecture_category_id = null;
    public string $title = '';
    public string $slug = '';
    public ?string $summary = '';
    public ?string $content = '';
    public bool $is_published = true;
    public int $order = 0;

    public function mount(?int $id = null): void
    {
        $this->topicId = $id;

        $lecture = $id ? Lecture::findOrFail($id) : new Lecture();
        $this->loadSeo($lecture);
        if ($id) {
            $this->loadPublicationDate($lecture);
            $this->lecture_category_id = $lecture->lecture_category_id;
            $this->title = $lecture->title;
            $this->slug = $lecture->slug;
            $this->summary = $lecture->summary;
            $this->content = $lecture->content;
            $this->is_published = (bool) $lecture->is_published;
            $this->order = (int) $lecture->order;
        } else {
            $this->loadPublicationDate();
            $this->lecture_category_id = LectureCategory::orderBy('order')->first()?->id;
            $this->is_published = true;
            $this->order = (Lecture::max('order') ?? 0) + 1;
        }
    }

    public function updatedTitle($value): void
    {
        if (!$this->topicId) {
            $this->slug = Str::slug($value);
        }
    }

    public function rules(): array
    {
        return [
            ...$this->seoRules(),
            'published_at' => 'nullable|date',
            'lecture_category_id' => 'required|exists:lecture_categories,id',
            'title' => 'required|string|max:255',
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('lectures', 'slug')->ignore($this->topicId),
            ],
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'is_published' => 'boolean',
            'order' => 'required|integer|min:0',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            if ($this->topicId) {
                $lecture = Lecture::findOrFail($this->topicId);
                $validated['published_at'] = $this->publicationDateForSave($lecture);
                $lecture->update($validated);


                session()->flash('success', 'Konu anlatımı başarıyla güncellendi.');
            } else {
                $lecture = Lecture::create($validated);


                session()->flash('success', 'Yeni konu anlatımı başarıyla eklendi.');
            }
        });

        if (!$this->topicId) {
            return redirect()->route('admin.topics');
        }
    }

    public function render()
    {
        $categories = LectureCategory::orderBy('order')->get();

        return view('livewire.admin.topic-form', compact('categories'))->with('creatorRecord', $this->topicId ? Lecture::find($this->topicId) : null);
    }
}
