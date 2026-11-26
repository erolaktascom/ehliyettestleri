<?php

namespace App\Livewire\Admin;

use App\Models\LectureCategory;
use App\Support\StudyCategoryIcon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ContentCategoryForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsSeo;
    public ?int $categoryId = null;
    public string $name = '';
    public string $slug = '';
    public ?string $description = '';
    public ?string $icon = null;
    public int $order = 0;

    public function mount(?int $id = null): void
    {
        $cat = $id ? LectureCategory::findOrFail($id) : new LectureCategory();
        $this->loadSeo($cat);
        if ($id) {
            $this->categoryId = $cat->id;
            $this->name = $cat->name;
            $this->slug = $cat->slug;
            $this->description = $cat->description;
            $this->icon = StudyCategoryIcon::selected($cat->icon);
            $this->order = (int) $cat->order;
        } else {
            $this->order = (LectureCategory::max('order') ?? 0) + 1;
        }
    }

    public function updatedName($value): void
    {
        if (!$this->categoryId) {
            $this->slug = Str::slug($value);
        }
    }

    public function rules(): array
    {
        return [
            ...$this->seoRules(),
            'name' => 'required|string|max:255',
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('lecture_categories', 'slug')->ignore($this->categoryId),
            ],
            'description' => 'nullable|string',
            'icon' => ['nullable', 'string', Rule::in(array_keys(StudyCategoryIcon::OPTIONS))],
            'order' => 'required|integer|min:0',
        ];
    }

    public function save()
    {
        $validated = $this->validate();
        $validated['icon'] = StudyCategoryIcon::selected($this->icon);

        DB::transaction(function () use ($validated) {
            if ($this->categoryId) {
                $cat = LectureCategory::findOrFail($this->categoryId);
                $cat->update($validated);


                session()->flash('success', 'Konu kategorisi güncellendi.');
            } else {
                $cat = LectureCategory::create($validated);


                session()->flash('success', 'Yeni konu kategorisi eklendi.');
            }
        });

        if (!$this->categoryId) {
            return redirect()->route('admin.content-categories');
        }
    }

    public function render()
    {
        return view('livewire.admin.content-category-form');
    }
}
