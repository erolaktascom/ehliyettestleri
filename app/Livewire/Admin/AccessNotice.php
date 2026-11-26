<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class AccessNotice extends Component
{
    public function render()
    {
        return view('livewire.admin.access-notice')->layout('layouts.admin', ['title' => 'Panel Erişimi']);
    }
}
