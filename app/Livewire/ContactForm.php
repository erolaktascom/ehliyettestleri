<?php

namespace App\Livewire;

use App\Services\ContactMessageSubmission;
use Livewire\Component;

class ContactForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $subject = '';
    public string $message = '';
    public string $website = ''; // Honeypot
    public bool $submitted = false;

    public function submit(ContactMessageSubmission $submission)
    {
        $submission->submit($this->only(['name', 'email', 'phone', 'subject', 'message', 'website']));

        $this->resetValidation();
        $this->reset(['name', 'email', 'phone', 'subject', 'message']);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
