<?php

namespace App\Livewire\Admin;

use App\Models\Integration;
use App\Support\IntegrationCatalog;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\{Layout, Locked};
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Entegrasyon Ayarları'])]
class IntegrationForm extends Component
{
    #[Locked]
    public int $integrationId;
    public array $fields = [];
    public bool $is_active = false;

    private function record(): Integration
    {
        return Integration::findOrFail($this->integrationId);
    }

    public function mount(int $id): void
    {
        $this->integrationId = $id;
        $record = $this->record();
        $definition = IntegrationCatalog::get($record->integration_key ?? '');
        $settings = $record->configuration ?? [];
        foreach ($definition['fields'] as $key => $field) {
            // Stored secrets never enter the HTML or the Livewire snapshot.
            $this->fields[$key] = $field['type'] === 'password' ? '' : ($settings[$key] ?? '');
        }
        $this->is_active = $definition['ready'] && $record->is_active;
    }

    public function save(): void
    {
        $record = $this->record();
        $definition = IntegrationCatalog::get($record->integration_key ?? '');
        $rules = ['is_active' => 'boolean', 'fields' => 'required|array:'.implode(',', array_keys($definition['fields']))];
        foreach ($definition['fields'] as $key => $field) $rules['fields.'.$key] = 'nullable|string|max:'.$field['max'];
        $validated = $this->validate($rules);
        if ($validated['is_active'] && !$definition['ready']) {
            throw ValidationException::withMessages(['is_active' => 'Bu entegrasyon henüz etkinleştirmeye hazır değil. Ayarlar pasif olarak kaydedilebilir.']);
        }
        $settings = $record->configuration ?? [];
        foreach ($definition['fields'] as $key => $field) {
            $value = $validated['fields'][$key] ?? '';
            if ($field['type'] === 'password' && $value === '') continue;
            $settings[$key] = $value;
        }
        if ($record->integration_key === 'smtp') unset($settings['from_name']);
        if ($validated['is_active']) {
            foreach ($definition['fields'] as $key => $field) {
                if (trim($settings[$key] ?? '') === '') throw ValidationException::withMessages(['fields.'.$key => $field['label'].' gerekli.']);
            }
        }
        if ($validated['is_active'] && $record->integration_key === 'smtp') {
            validator(['fields' => $settings], [
                'fields.host' => ['required', 'regex:/^[a-zA-Z0-9.-]+$/'], 'fields.port' => 'required|integer|between:1,65535',
                'fields.encryption' => 'required|in:tls,ssl', 'fields.from_address' => 'required|email',
            ])->validate();
        }
        $record->update(['configuration' => $settings, 'is_active' => $validated['is_active']]);
        foreach ($definition['fields'] as $key => $field) {
            if ($field['type'] === 'password') $this->fields[$key] = '';
        }
        session()->flash('success', 'Entegrasyon ayarları kaydedildi.');
    }

    public function render()
    {
        $record = $this->record();
        $definition = IntegrationCatalog::get($record->integration_key ?? '');
        $settings = $record->configuration ?? [];
        $savedSecrets = [];
        foreach ($definition['fields'] as $key => $field) {
            if ($field['type'] === 'password') $savedSecrets[$key] = ($settings[$key] ?? '') !== '';
        }
        return view('livewire.admin.integration-form', compact('definition', 'savedSecrets'));
    }
}
