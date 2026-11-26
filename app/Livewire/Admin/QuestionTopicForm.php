<?php

namespace App\Livewire\Admin;

use App\Models\TopicCategory;
use App\Support\StudyCategoryIcon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class QuestionTopicForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsSeo;
    public ?int $topicId = null;
    public string $name = '';
    public string $slug = '';
    public ?string $description = '';
    public ?string $icon = null;
    public int $order = 0;
    public bool $is_active = true;

    public function mount(?int $id = null): void
    {
        $topic = $id ? TopicCategory::findOrFail($id) : new TopicCategory();
        $this->loadSeo($topic);
        if ($id) {
            $this->topicId = $topic->id;
            $this->name = $topic->name;
            $this->slug = $topic->slug;
            $this->description = $topic->description;
            $this->icon = StudyCategoryIcon::selected($topic->icon);
            $this->order = (int) $topic->order;
            $this->is_active = (bool) $topic->is_active;
        } else {
            $this->order = (TopicCategory::max('order') ?? 0) + 1;
            $this->is_active = true;
        }
    }

    public function updatedName($value): void
    {
        if (!$this->topicId) {
            $this->slug = Str::slug($value);
        }
    }

    public function rules(): array
    {
        return [
            ...$this->seoRules(),
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:topic_categories,slug,' . ($this->topicId ?? 'NULL'),
            'description' => 'nullable|string',
            'icon' => ['nullable', 'string', Rule::in(array_keys(StudyCategoryIcon::OPTIONS))],
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function save()
    {
        $validated = $this->validate();
        $validated['icon'] = StudyCategoryIcon::selected($this->icon);

        if ($this->topicId) {
            TopicCategory::findOrFail($this->topicId)->update($validated);
            session()->flash('success', 'Soru kategorisi başarıyla güncellendi.');
        } else {
            TopicCategory::create($validated);
            session()->flash('success', 'Yeni soru kategorisi başarıyla oluşturuldu.');
        }

        if (!$this->topicId) {
            return redirect()->route('admin.question-topics');
        }
    }

    public function render()
    {
        return view('livewire.admin.question-topic-form');
    }
}
