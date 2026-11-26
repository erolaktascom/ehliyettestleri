<?php

namespace App\Livewire\Admin;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.admin')]
class BlogPostForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsSeo;
    use \App\Livewire\Admin\Concerns\ResolvesEditorVideo;
    use \App\Livewire\Admin\Concerns\UploadsEditorImages;
    #[Locked]
    public ?int $postId = null;
    public $featuredImageUpload;
    public bool $removeFeaturedImage = false;

    // Form fields
    public ?int $blog_category_id = null;
    public string $title = '';
    public string $slug = '';
    #[Locked]
    public string $author_name = '';
    public ?string $published_at = null;
    public bool $is_published = true;
    public ?string $summary = '';
    public ?string $content = '';

    public function mount(?int $id = null): void
    {
        $this->postId = $id;

        $post = $id ? BlogPost::findOrFail($id) : new BlogPost();
        $this->loadSeo($post);
        if ($id) {
            $this->blog_category_id = $post->blog_category_id;
            $this->title = $post->title;
            $this->slug = $post->slug;
            $this->author_name = $post->author_name;
            $this->published_at = $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : null;
            $this->is_published = (bool) $post->is_published;
            $this->summary = $post->summary;
            $this->content = $post->content;
        } else {
            $this->blog_category_id = BlogCategory::first()?->id;
            $this->published_at = now()->format('Y-m-d\TH:i');
            $this->is_published = true;
            $this->author_name = auth()->user()?->name ?? 'Ehliyet Rehberi';
        }
    }

    public function updatedTitle($value): void
    {
        if (!$this->postId) {
            $this->slug = Str::slug($value);
        }
    }

    public function rules(): array
    {
        return [
            ...$this->seoRules(),
            'blog_category_id' => 'required|exists:blog_categories,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blog_posts,slug,' . ($this->postId ?? 'NULL'),
            'published_at' => 'nullable|date',
            'is_published' => 'boolean',
            'summary' => 'nullable|string',
            'content' => 'required|string',
            'featuredImageUpload' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096',
            'removeFeaturedImage' => 'boolean',
        ];
    }

    public function updatedFeaturedImageUpload(): void
    {
        $this->validateOnly('featuredImageUpload');
        $this->removeFeaturedImage = false;
    }

    public function save()
    {
        $validated = $this->validate();
        unset($validated['featuredImageUpload'], $validated['removeFeaturedImage']);
        $post = $this->postId ? BlogPost::findOrFail($this->postId) : new BlogPost();
        $oldPath = $post->featured_image_path;
        $newPath = null;
        if ($this->postId && $post->published_at?->format('Y-m-d\TH:i') === $this->published_at) {
            $validated['published_at'] = $post->published_at;
        }
        if (!$this->postId) $validated['author_name'] = auth()->user()->name;
        try {
            if ($this->featuredImageUpload) {
                $newPath = $this->featuredImageUpload->store('blog-images', 'public');
                if (!$newPath) throw new \RuntimeException('Görsel kaydedilemedi.');
                $validated['featured_image_path'] = $newPath;
            } elseif ($this->removeFeaturedImage) {
                $validated['featured_image_path'] = null;
            }
            $post->fill($validated)->save();
        } catch (\Throwable $exception) {
            if ($newPath) \Illuminate\Support\Facades\Storage::disk('public')->delete($newPath);
            throw $exception;
        }
        if ($oldPath !== $post->featured_image_path) BlogPost::deleteUnusedFeaturedImage($oldPath);
        $this->reset('featuredImageUpload', 'removeFeaturedImage');
        session()->flash('success', $this->postId ? 'Blog yazısı başarıyla güncellendi.' : 'Yeni blog yazısı başarıyla eklendi.');
        if (!$this->postId) return redirect()->route('admin.blog-posts');
    }

    public function render()
    {
        BlogPost::syncPublicationDates();
        $categories = BlogCategory::orderBy('name')->get();

        return view('livewire.admin.blog-post-form', compact('categories'))->with('creatorRecord', $this->postId ? BlogPost::find($this->postId) : null);
    }
}
