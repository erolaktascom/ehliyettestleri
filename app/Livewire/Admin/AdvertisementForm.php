<?php

namespace App\Livewire\Admin;

use App\Models\Advertisement;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Reklam Alanı Formu'])]
class AdvertisementForm extends Component
{
    #[\Livewire\Attributes\Locked]
    public ?int $adId = null;
    #[\Livewire\Attributes\Locked]
    public string $device = 'desktop';
    public string $title = '';
    public string $location = 'Header';
    public array $audience = ['everyone'];
    public bool $is_active = true;
    public ?string $code = '';

    public function mount(?int $id = null, ?string $device = null): void
    {
        $this->device = $device ?? ($id ? Advertisement::findOrFail($id)->device : 'desktop');
        abort_unless(isset(Advertisement::DEVICES[$this->device]), 404);
        if ($id) {
            $a = Advertisement::where('device', $this->device)->findOrFail($id);
            $this->adId = $a->id;
            $this->title = $a->title;
            $this->location = $a->location;
            $this->audience = array_values(array_intersect($a->audience ?? [], array_keys(Advertisement::SELECTABLE_AUDIENCES)));
            $this->is_active = (bool) $a->is_active;
            $this->code = $a->code;
        } else {
            $this->location = 'Header';
            $this->is_active = true;
        }
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'audience' => 'required|array|min:1|max:3',
            'audience.*' => 'required|string|distinct|in:'.implode(',', array_keys(Advertisement::SELECTABLE_AUDIENCES)),
            'location' => 'required|in:'.implode(',', array_keys(Advertisement::locations($this->device))),
            'is_active' => 'boolean',
            'code' => 'nullable|string|max:100000',
        ];
    }

    public function save()
    {
        $validated = $this->validate() + ['device' => $this->device];
        if (in_array('everyone', $validated['audience'], true)) $validated['audience'] = ['everyone'];
        $this->audience = $validated['audience'];

        if ($this->adId) {
            Advertisement::where('device', $this->device)->findOrFail($this->adId)->update($validated);
            session()->flash('success', 'Reklam alanı güncellendi.');
        } else {
            Advertisement::create($validated);
            session()->flash('success', 'Yeni reklam alanı eklendi.');
        }

        if (!$this->adId) {
            return redirect()->route('admin.advertisements.'.$this->device);
        }
    }

    public function render()
    {
        return view('livewire.admin.advertisement-form');
    }

    public function toggleAudience(string $key): void
    {
        abort_unless(isset(Advertisement::SELECTABLE_AUDIENCES[$key]), 422);
        if ($key === 'everyone') {
            $this->audience = in_array('everyone', $this->audience, true) ? [] : ['everyone'];
        } else {
            $selected = in_array($key, $this->audience, true);
            $this->audience = array_values(array_diff($this->audience, ['everyone', $key]));
            if (!$selected) $this->audience[] = $key;
        }
        $this->resetValidation('audience');
    }

    protected function messages(): array
    {
        return ['audience.required' => 'En az bir hedef kitle seçin.', 'audience.*.in' => 'Geçerli bir hedef kitle seçin.'];
    }
}
