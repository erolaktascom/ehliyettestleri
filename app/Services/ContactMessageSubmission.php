<?php

namespace App\Services;

use App\Models\ContactMessage;
use Illuminate\Support\Facades\Validator;

class ContactMessageSubmission
{
    public function submit(array $input): ?ContactMessage
    {
        // Livewire and ordinary HTTP submissions must normalize inputs identically.
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $input[$key] = trim($value);
            }
        }

        // Silently discard honeypot submissions without creating a message.
        if (($input['website'] ?? '') !== '') {
            return null;
        }

        if (($input['phone'] ?? '') === '') {
            $input['phone'] = null;
        }

        $validated = Validator::make($input, [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'subject' => 'required|string|min:3|max:200',
            'message' => 'required|string|min:10|max:3000',
        ])->validate();

        return ContactMessage::create($validated + ['is_read' => false]);
    }
}
