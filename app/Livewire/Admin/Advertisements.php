<?php

namespace App\Livewire\Admin;

use App\Models\Advertisement;
use Livewire\Component;
use Livewire\WithPagination;

class Advertisements extends Component
{
    use WithPagination;

    #[\Livewire\Attributes\Locked]
    public string $device = 'desktop';
    public function mount(string $device = 'desktop'): void {
        abort_unless(isset(Advertisement::DEVICES[$device]), 404);
        $this->device = $device;
    }
    public string $search = '';
    public string $filterLocation = '';
    public string $filterAudience = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterAudience() { $this->resetPage(); }
    public function updatingFilterLocation() { $this->resetPage(); }

    public function delete(int $id)
    {
        Advertisement::where('device', $this->device)->findOrFail($id)->delete();
        session()->flash('success', 'Reklam alanı silindi.');
    }

    public function render()
    {
        $query = Advertisement::where('device', $this->device);

        if ($this->search) {
            $query->where(fn ($q) => $q->where('title', 'like', '%' . $this->search . '%')->orWhere('location', 'like', '%' . $this->search . '%'));
        }

        if ($this->filterLocation) {
            $query->where('location', $this->filterLocation);
        }

        if ($this->filterAudience) {
            $query->whereJsonContains('audience', $this->filterAudience);
        }

        $ads = $query->latest()->paginate(15);

        return view('livewire.admin.advertisements', [
            'ads' => $ads,
        ])->layout('layouts.admin', ['title' => 'Reklam Alanları']);
    }
}
