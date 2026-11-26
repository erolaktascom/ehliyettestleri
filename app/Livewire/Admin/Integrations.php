<?php

namespace App\Livewire\Admin;

use App\Models\Integration;
use App\Support\IntegrationCatalog;
use Livewire\Component;

class Integrations extends Component
{
    public function render()
    {
        return view('livewire.admin.integrations', [
            'catalog' => IntegrationCatalog::all(),
            'integrations' => Integration::whereIn('integration_key', array_keys(IntegrationCatalog::all()))->get()->keyBy('integration_key'),
        ])->layout('layouts.admin', ['title' => 'Entegrasyonlar']);
    }
}
