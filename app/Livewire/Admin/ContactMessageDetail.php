<?php

namespace App\Livewire\Admin;

use App\Models\ContactMessage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Mesaj Detayı'])]
class ContactMessageDetail extends Component
{
    public ContactMessage $message;

    public function mount(int $id): void
    {
        $this->message = ContactMessage::findOrFail($id);
        if (!$this->message->is_read) {
            $this->message->update(['is_read' => true]);
        }
    }

    public function render()
    {
        return view('livewire.admin.contact-message-detail');
    }
}
