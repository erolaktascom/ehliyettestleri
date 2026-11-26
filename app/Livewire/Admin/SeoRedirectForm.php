<?php

namespace App\Livewire\Admin;

use App\Models\SeoRedirect;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'SEO Yönlendirme Formu'])]
class SeoRedirectForm extends Component
{
    #[\Livewire\Attributes\Locked]
    public ?int $redirectId = null;
    public array $sources = [['url' => '', 'match_type' => 'exact', 'ignore_case' => false]];
    public string $new_url = '';
    public string $type = '301';
    public string $status = 'Aktif';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $r = SeoRedirect::findOrFail($id);
            $this->redirectId = $r->id;
            $this->sources = $r->sourceEntries();
            $this->new_url = $r->new_url;
            $this->type = $r->type;
            $this->status = $r->status;
        } else {
            $this->type = '301';
            $this->status = 'Aktif';
        }
    }

    public function rules(): array
    {
        return [
            'sources' => 'required|array|min:1|max:20',
            'sources.*' => 'array:url,match_type,ignore_case',
            'sources.*.url' => 'required|string|max:255',
            'sources.*.match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'sources.*.ignore_case' => 'required|boolean',
            'new_url' => 'exclude_if:type,410|required|string|max:255',
            'type' => 'required|in:301,302,410',
            'status' => 'required|in:Aktif,Pasif',
        ];
    }

    public function updatedType(): void
    {
        $this->resetValidation('new_url');
    }

    public function addSource(): void
    {
        if (count($this->sources) >= 20) return;
        $this->sources[] = ['url' => '', 'match_type' => 'exact', 'ignore_case' => false];
    }

    public function removeSource(int $index): void
    {
        if (count($this->sources) <= 1 || !array_key_exists($index, $this->sources)) return;
        array_splice($this->sources, $index, 1);
        $this->resetValidation();
    }

    public function validationAttributes(): array
    {
        return ['sources' => 'kaynak adresler', 'sources.*.url' => 'kaynak adres',
            'sources.*.match_type' => 'eşleşme türü', 'sources.*.ignore_case' => 'harf duyarlılığı'];
    }

    public function save()
    {
        $validated = \App\Support\SeoRedirectRules::validate($this->validate(), $this->redirectId);

        if ($this->redirectId) {
            $rule = SeoRedirect::findOrFail($this->redirectId);
            $rule->fill($validated);
            if ($rule->isDirty(['sources','old_url','new_url','type','status'])) {
                $rule->source_model = null;
                $rule->source_id = null;
            }
            $rule->save();
            session()->flash('success', 'Yönlendirme kuralı güncellendi.');
        } else {
            SeoRedirect::create($validated);
            session()->flash('success', 'Yeni yönlendirme eklendi.');
        }

        if (!$this->redirectId) {
            return redirect()->route('admin.seo-redirects');
        }
    }

    public function render()
    {
        return view('livewire.admin.seo-redirect-form');
    }
}
