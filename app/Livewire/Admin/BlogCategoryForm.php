<?php

namespace App\Livewire\Admin;

use App\Models\BlogCategory;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Blog Kategorisi Formu'])]
class BlogCategoryForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsSeo;
    public ?int $categoryId = null;
    public string $name = '';
    public string $slug = '';

    public function mount(?int $id = null): void
    {
        $cat = $id ? BlogCategory::findOrFail($id) : new BlogCategory();
        $this->loadSeo($cat);
        if ($id) {
            $this->categoryId = $cat->id;
            $this->name = $cat->name;
            $this->slug = $cat->slug;
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
            'slug' => 'required|string|alpha_dash|max:255|unique:blog_categories,slug,' . ($this->categoryId ?? 'NULL'),
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        if ($this->categoryId) {
            BlogCategory::findOrFail($this->categoryId)->update($validated);
            session()->flash('success', 'Blog kategorisi güncellendi.');
        } else {
            BlogCategory::create($validated);
            session()->flash('success', 'Yeni blog kategorisi eklendi.');
        }

        if (!$this->categoryId) {
            return redirect()->route('admin.blog-categories');
        }
    }

    public function render()
    {
        return view('livewire.admin.blog-category-form');
    }
}
