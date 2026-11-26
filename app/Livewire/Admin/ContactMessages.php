<?php

namespace App\Livewire\Admin;

use App\Models\ContactMessage;
use Livewire\Component;
use Livewire\WithPagination;

class ContactMessages extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    public function delete(int $id): void
    {
        ContactMessage::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Mesaj başarıyla silindi.');
    }

    public function render()
    {
        $query = ContactMessage::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('subject', 'like', '%' . $this->search . '%')
                  ->orWhere('message', 'like', '%' . $this->search . '%');
            });
        }

        if (in_array($this->statusFilter, ['read', 'unread'], true)) {
            $query->where('is_read', $this->statusFilter === 'read');
        }

        $messages = $query->latest()->paginate(15);

        return view('livewire.admin.contact-messages', [
            'messages' => $messages,
        ])->layout('layouts.admin', ['page_title' => 'İletişim Mesajları']);
    }
}
