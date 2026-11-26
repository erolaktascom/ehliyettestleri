<?php

namespace App\Livewire\Admin;

use App\Models\TrafficSignCategory;
use App\Support\StudyCategoryIcon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'İşaret Kategorisi Formu'])]
class TrafficSignCategoryForm extends Component
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
        $cat = $id ? TrafficSignCategory::findOrFail($id) : new TrafficSignCategory();
        $this->loadSeo($cat);
        if ($id) {
            $this->categoryId = $cat->id;
            $this->name = $cat->name;
            $this->slug = $cat->slug;
            $this->description = $cat->description;
            $this->icon = StudyCategoryIcon::selected($cat->icon);
            $this->order = (int) $cat->order;
        } else {
            $this->order = (TrafficSignCategory::max('order') ?? 0) + 1;
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
            'slug' => 'required|string|max:255|unique:traffic_sign_categories,slug,' . ($this->categoryId ?? 'NULL'),
            'description' => 'nullable|string',
            'icon' => ['nullable', 'string', Rule::in(array_keys(StudyCategoryIcon::OPTIONS))],
            'order' => 'required|integer|min:0',
        ];
    }

    public function save()
    {
        $validated = $this->validate();
        $validated['icon'] = StudyCategoryIcon::selected($this->icon);

        if ($this->categoryId) {
            TrafficSignCategory::findOrFail($this->categoryId)->update($validated);
            session()->flash('success', 'İşaret kategorisi güncellendi.');
        } else {
            TrafficSignCategory::create($validated);
            session()->flash('success', 'Yeni işaret kategorisi eklendi.');
        }

        if (!$this->categoryId) {
            return redirect()->route('admin.traffic-sign-categories');
        }
    }

    public function render()
    {
        return view('livewire.admin.traffic-sign-category-form');
    }
}
