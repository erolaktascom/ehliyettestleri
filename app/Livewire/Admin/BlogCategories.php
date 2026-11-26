<?php

namespace App\Livewire\Admin;

use App\Models\BlogCategory;
use Livewire\Component;
use Livewire\WithPagination;

class BlogCategories extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        BlogCategory::findOrFail($id)->delete();
        session()->flash('success', 'Blog kategorisi silindi.');
    }

    public function render()
    {
        $query = BlogCategory::withCount('posts');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%');
        }

        $categories = $query->latest()->paginate(15);

        return view('livewire.admin.blog-categories', [
            'categories' => $categories,
        ])->layout('layouts.admin', ['title' => 'Blog Kategorileri']);
    }
}
