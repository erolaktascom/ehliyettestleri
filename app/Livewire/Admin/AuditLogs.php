<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogs extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        AuditLog::findOrFail($id)->delete();
        \App\Support\PanelAudit::bulk('İşlem Geçmişi', 1);
        session()->flash('success', 'Denetim kaydı silindi.');
    }

    public function clearAll()
    {
        $count = AuditLog::count();
        AuditLog::query()->delete();
        \App\Support\PanelAudit::bulk('İşlem Geçmişi', $count);
        session()->flash('success', 'Tüm denetim kayıtları temizlendi.');
    }

    public function render()
    {
        $query = AuditLog::query();

        if ($this->search) {
            $query->where('user_name', 'like', '%' . $this->search . '%')
                  ->orWhere('record_type', 'like', '%' . $this->search . '%')
                  ->orWhere('record_title', 'like', '%' . $this->search . '%');
        }

        $logs = $query->latest()->paginate(20);

        return view('livewire.admin.audit-logs', [
            'logs' => $logs,
        ])->layout('layouts.admin', ['title' => 'İşlem Geçmişi']);
    }
}
