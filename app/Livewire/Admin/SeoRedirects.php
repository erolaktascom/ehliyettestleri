<?php

namespace App\Livewire\Admin;

use App\Models\SeoRedirect;
use Livewire\Component;
use Livewire\WithPagination;

class SeoRedirects extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        SeoRedirect::findOrFail($id)->delete();
        session()->flash('success', 'Yönlendirme silindi.');
    }

    public function render()
    {
        $query = SeoRedirect::query();

        if ($this->search) {
            $query->where('old_url', 'like', '%' . $this->search . '%')
                  ->orWhere('sources', 'like', '%' . $this->search . '%')
                  ->orWhere('new_url', 'like', '%' . $this->search . '%');
        }

        $redirects = $query->latest()->paginate(15);

        return view('livewire.admin.seo-redirects', [
            'redirects' => $redirects,
        ])->layout('layouts.admin', ['title' => 'SEO Yönlendirmeleri'])
            // Retire redirects cached before no-store was introduced. Keep cookies/storage.
            ->response(fn ($response) => $response
                ->header('Clear-Site-Data', '"cache"')
                ->header('Cache-Control', 'private, no-store, no-cache, max-age=0, must-revalidate'));
    }
}
