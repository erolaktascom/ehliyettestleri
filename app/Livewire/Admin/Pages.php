<?php

namespace App\Livewire\Admin;

use App\Models\Page;
use Livewire\Component;
use Livewire\WithPagination;

class Pages extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function delete(int $id)
    {
        Page::findOrFail($id)->delete();
        session()->flash('success', 'Sayfa silindi.');
    }

    public function render()
    {
        $query = Page::query();

        if ($this->search) {
            $query->where(function ($search) {
                $search->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return view('livewire.admin.pages', [
            'pages' => $query->latest('published_at')->paginate(15),
        ])->layout('layouts.admin');
    }
}
