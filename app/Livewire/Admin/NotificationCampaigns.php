<?php

namespace App\Livewire\Admin;

use App\Models\NotificationCampaign;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCampaigns extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function delete(int $id)
    {
        NotificationCampaign::findOrFail($id)->delete();
        session()->flash('success', 'Bildirim silindi.');
    }

    public function render()
    {
        $query = NotificationCampaign::query();

        if ($this->search) {
            $query->where(fn ($q) => $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('type', 'like', '%' . $this->search . '%'));
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $campaigns = $query->latest()->paginate(15);

        return view('livewire.admin.notification-campaigns', [
            'campaigns' => $campaigns,
        ])->layout('layouts.admin', ['title' => 'Bildirim Kampanyaları']);
    }
}
