<?php
namespace App\Livewire\Admin;

use App\Models\SeoRouteSetting;
use App\Support\SeoPageCatalog;
use Livewire\Component;

class SeoPages extends Component
{
    use \App\Livewire\Admin\Concerns\EditsSeo;
    public string $selectedPage = 'home';

    public function mount(): void { $this->updatedSelectedPage(); }

    public function updatedSelectedPage(): void
    {
        $this->validate(['selectedPage' => ['required', \Illuminate\Validation\Rule::in(array_keys(SeoPageCatalog::choices()))]]);
        $this->resetValidation();
        $this->loadSeo(SeoRouteSetting::where('route_key', $this->selectedPage)->first());
        if ($this->seo_index === 'inherit') $this->seo_index = 'index';
    }

    public function save(): void
    {
        $data = $this->validate([
            'selectedPage' => ['required', \Illuminate\Validation\Rule::in(array_keys(SeoPageCatalog::choices()))],
            ...$this->seoRules(),
            'seo_index' => 'required|in:index,noindex',
        ]);
        unset($data['selectedPage']);
        SeoRouteSetting::updateOrCreate(['route_key' => $this->selectedPage], $data);
        session()->flash('success', 'Sayfanın SEO ayarları kaydedildi.');
    }

    public function render()
    {
        $choices = SeoPageCatalog::choices();
        return view('livewire.admin.seo-pages', ['choices' => $choices, 'pageUrl' => isset($choices[$this->selectedPage]) ? SeoPageCatalog::url($this->selectedPage) : null])
            ->layout('layouts.admin', ['title' => 'Sayfa SEO Ayarları']);
    }
}