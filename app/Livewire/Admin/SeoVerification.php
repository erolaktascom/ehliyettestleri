<?php

namespace App\Livewire\Admin;

use App\Models\SeoSetting;
use App\Support\SiteVerification;
use Livewire\Component;

class SeoVerification extends Component
{
    public array $verification = [];
    public string $custom_verification = '';

    public function mount(): void
    {
        $this->loadVerification();
    }

    protected function loadVerification(): void
    {
        $tags = SeoSetting::first()?->verification_tags ?? [];
        foreach (SiteVerification::PROVIDERS as $key => $provider) {
            $this->verification[$key] = $tags[$provider['name']] ?? '';
        }
        $this->custom_verification = SiteVerification::customHtml($tags);
    }

    public function save(): void
    {
        $this->validate([
            'verification' => 'array:'.implode(',', array_keys(SiteVerification::PROVIDERS)),
            'verification.*' => 'nullable|string|max:4096',
            'custom_verification' => 'nullable|string|max:20000',
        ]);
        $verification = SiteVerification::normalize($this->verification, $this->custom_verification);
        $setting = SeoSetting::first() ?? new SeoSetting();
        $setting->verification_tags = $verification['tags'];
        $setting->save();
        $this->loadVerification();
        session()->flash('success', 'Doğrulama ayarları başarıyla kaydedildi.');
    }

    public function render()
    {
        return view('livewire.admin.seo-verification', ['verificationProviders' => SiteVerification::PROVIDERS])
            ->layout('layouts.admin', ['title' => 'SEO Doğrulama Ayarları']);
    }
}