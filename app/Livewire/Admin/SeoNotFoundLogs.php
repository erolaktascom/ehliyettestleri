<?php

namespace App\Livewire\Admin;

use App\Models\SeoNotFoundLog;
use Livewire\Component;
use Livewire\WithPagination;

class SeoNotFoundLogs extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        SeoNotFoundLog::findOrFail($id)->delete();
        session()->flash('success', '404 kaydı silindi.');
    }

    public function clearAll()
    {
        $count = SeoNotFoundLog::count();
        SeoNotFoundLog::query()->delete();
        \App\Support\PanelAudit::bulk('404 Kayıtları', $count);
        session()->flash('success', 'Tüm 404 kayıtları temizlendi.');
    }

    public function render()
    {
        $query = SeoNotFoundLog::query();

        if ($this->search) {
            $query->where('url', 'like', '%' . $this->search . '%');
        }

        $logs = $query->orderByDesc('request_count')->paginate(20);

        return view('livewire.admin.seo-not-found-logs', [
            'logs' => $logs,
        ])->layout('layouts.admin', ['title' => '404 Kayıtları']);
    }
}
