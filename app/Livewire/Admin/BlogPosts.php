<?php

namespace App\Livewire\Admin;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Livewire\Component;
use Livewire\WithPagination;

class BlogPosts extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterCategory = '';
    public string $filterStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterCategory() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $post = BlogPost::findOrFail($id);
        $post->is_published = !$post->is_published;
        if ($post->is_published && !$post->published_at) {
            $post->published_at = now();
        }
        $post->save();
        session()->flash('success', 'Blog yazısının yayın durumu güncellendi.');
    }

    public function delete(int $id)
    {
        BlogPost::findOrFail($id)->delete();
        session()->flash('success', 'Blog yazısı silindi.');
    }

    public function render()
    {
        BlogPost::syncPublicationDates();
        $query = BlogPost::with('category');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%')
                  ->orWhere('summary', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterCategory) {
            $query->where('blog_category_id', $this->filterCategory);
        }

        if ($this->filterStatus === 'scheduled') {
            $query->where('is_published', true)->where('published_at', '>', now());
        } elseif ($this->filterStatus === '1') {
            $query->visible();
        } elseif ($this->filterStatus === '0') {
            $query->where('is_published', false);
        }

        return view('livewire.admin.blog-posts', [
            'posts' => $query->latest('published_at')->paginate(15),
            'categories' => BlogCategory::orderBy('name')->orderBy('id')->get(),
        ])->layout('layouts.admin');
    }
}
