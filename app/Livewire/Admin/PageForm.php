<?php

namespace App\Livewire\Admin;

use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class PageForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsSeo;
    use \App\Livewire\Admin\Concerns\ResolvesEditorVideo;
    use \App\Livewire\Admin\Concerns\UploadsEditorImages;
    public ?int $pageId = null;

    // Form fields
    public string $title = '';
    public string $slug = '';
    public string $status = 'published';
    public ?string $published_at = null;
    public ?string $short_description = '';
    public ?string $content = '';

    public function mount(?int $id = null): void
    {
        $this->pageId = $id;
        $this->seo_index = 'index';

        if ($this->pageId) {
            $p = Page::findOrFail($this->pageId);
            $this->loadSeo($p);
            if ($this->seo_index === 'inherit') $this->seo_index = 'index';
            $this->title = $p->title;
            $this->slug = $p->slug;
            $this->status = $p->status ?? 'published';
            $this->published_at = $p->published_at ? $p->published_at->format('Y-m-d\TH:i') : null;
            $this->short_description = $p->short_description;
            $this->content = $p->content;
        } else {
            $this->status = 'published';
            $this->published_at = now()->format('Y-m-d\TH:i');
        }
    }

    public function updatedTitle($value): void
    {
        if (!$this->pageId) {
            $this->slug = Str::slug($value);
        }
    }

    public function rules(): array
    {
        return [
            ...$this->seoRules(),
            'seo_index' => 'required|in:index,noindex',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . ($this->pageId ?? 'NULL'),
            'status' => 'required|in:published,draft',
            'published_at' => 'nullable|date',
            'short_description' => 'nullable|string',
            'content' => 'nullable|string',
        ];
    }

    public function save()
    {
        $validated = $this->validate();
        \App\Support\PageSlug::validate($validated['slug']);

        if ($this->pageId) {
            $page = Page::findOrFail($this->pageId);
            // Legacy inherit already means Index on standalone pages; a no-op save is not an edit.
            if ($page->seo_index === 'inherit' && $validated['seo_index'] === 'index') unset($validated['seo_index']);
            // The minute-precision input must not truncate an unchanged stored date.
            if ($page->published_at?->format('Y-m-d\TH:i') === $this->published_at) {
                $validated['published_at'] = $page->published_at;
            }
            $page->update($validated);
            session()->flash('success', 'Sayfa başarıyla güncellendi.');
        } else {
            Page::create($validated);
            session()->flash('success', 'Yeni sayfa başarıyla oluşturuldu.');
        }

        if (!$this->pageId) {
            return redirect()->route('admin.pages');
        }
    }

    public function render()
    {
        return view('livewire.admin.page-form')->with('creatorRecord', $this->pageId ? Page::find($this->pageId) : null);
    }
}
