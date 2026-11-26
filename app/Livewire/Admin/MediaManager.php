<?php

namespace App\Livewire\Admin;

use App\Support\MediaLibrary;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class MediaManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter = '';
    #[Locked] public ?string $pendingDelete = null;

    public function mount(): void { $this->authorizeMedia(); }
    private function authorizeMedia(): void
    {
        abort_unless(auth()->user()?->canAccessAdminRoute('admin.media'), 403);
    }
    public function updatedSearch(): void { $this->resetPage(); $this->cancelDelete(); }
    public function updatedFilter(): void { $this->resetPage(); $this->cancelDelete(); }
    public function confirmDelete(string $path): void
    {
        $this->authorizeMedia();
        abort_unless(app(MediaLibrary::class)->safeFile($path), 404);
        $this->resetValidation();
        $this->pendingDelete = $path;
    }
    public function cancelDelete(): void { $this->pendingDelete = null; $this->resetValidation(); }
    public function delete(): void
    {
        $this->authorizeMedia();
        if (!$this->pendingDelete) return;
        $path = $this->pendingDelete;
        app(MediaLibrary::class)->deleteUnused($path);
        \App\Models\AuditLog::create(['user_id' => auth()->id(), 'user_name' => auth()->user()->name,
            'record_type' => 'Medya', 'record_title' => $path, 'changed_fields' => 'Kullanılmayan görsel silindi']);
        $this->cancelDelete();
        $this->resetPage();
        session()->flash('success', 'Kullanılmayan görsel silindi.');
    }
    public function render()
    {
        $this->authorizeMedia();
        $files = app(MediaLibrary::class)->files();
        $counts = ['total' => $files->count(), 'used' => $files->filter(fn ($file) => count($file['uses']) > 0)->count()];
        $counts['unused'] = $counts['total'] - $counts['used'];
        $filtered = $files->filter(function ($file) {
            if ($this->filter === 'used' && !$file['uses']) return false;
            if ($this->filter === 'unused' && $file['uses']) return false;
            return $this->search === '' || str_contains(mb_strtolower($file['path'].' '.implode(' ', array_column($file['uses'], 'title'))), mb_strtolower($this->search));
        })->values();
        $media = new LengthAwarePaginator($filtered->forPage($this->getPage(), 24), $filtered->count(), 24, $this->getPage(), ['path' => route('admin.media')]);
        return view('livewire.admin.media-manager', compact('media', 'counts'))->layout('layouts.admin');
    }
}
