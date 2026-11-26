<?php

namespace App\Livewire\Admin;

use App\Models\SiteSetting;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Settings extends Component
{
    use WithFileUploads;

    public string $site_name = '';
    public string $site_url = '';
    public string $contact_email = SiteSetting::DEFAULT_CONTACT_EMAIL;
    public string $phone = '';
    public string $address = '';
    public string $site_description = SiteSetting::DEFAULT_DESCRIPTION;
    public string $copyright_text = SiteSetting::DEFAULT_COPYRIGHT;
    public array $social_links = [];
    public $logoUpload;
    public $faviconUpload;
    public bool $remove_logo = false;
    public bool $remove_favicon = false;

    public function mount()
    {
        $this->site_url = rtrim((string) config('app.url'), '/');
        $setting = SiteSetting::first();
        if ($setting) {
            $this->site_name = \App\Support\SiteBrand::name($setting);
            $this->contact_email = $setting->contact_email ?? SiteSetting::DEFAULT_CONTACT_EMAIL;
            $this->phone = $setting->phone ?? '';
            $this->address = $setting->address ?? '';
            $this->site_description = $setting->site_description ?? '';
            $this->copyright_text = $setting->copyright_text ?? '';
        } else {
            $this->site_name = \App\Support\SiteBrand::DEFAULT_NAME;
        }
        foreach (SiteSetting::SOCIAL_PLATFORMS as $key => $label) {
            $value = $setting?->social_links[$key] ?? '';
            $this->social_links[$key] = SiteSetting::socialUsername($key, $value) ?? $value;
        }
    }

    public function save(): void
    {
        foreach ($this->social_links as $key => $value) {
            if (is_string($value)) $this->social_links[$key] = preg_replace('/^@/', '', trim($value));
        }
        $data = $this->validate([
            'site_name' => 'required|string|min:2|max:255',
            'site_url' => 'required|string|max:2048',
            'contact_email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:2000',
            'site_description' => 'nullable|string|max:2000',
            'copyright_text' => 'nullable|string|max:500',
            'social_links' => 'array:'.implode(',', array_keys(SiteSetting::SOCIAL_PLATFORMS)),
            'social_links.*' => ['nullable', 'string', 'max:100', function ($attribute, $value, $fail) {
                if (!is_string($value) || ($value !== '' && !SiteSetting::validSocialUsername($value))) $fail('Bağlantı yerine kullanıcı adını girin. Harf, rakam, nokta, tire ve alt çizgi kullanabilirsiniz.');
            }],
            'logoUpload' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048|dimensions:max_width=4096,max_height=4096',
            'faviconUpload' => 'nullable|file|mimes:png,ico|max:512',
            'remove_logo' => 'boolean',
            'remove_favicon' => 'boolean',
        ]);
        $this->site_url = \App\Support\SiteAddress::normalize($this->site_url);
        unset($data['site_url']);
        unset($data['logoUpload'], $data['faviconUpload'], $data['remove_logo'], $data['remove_favicon']);
        $setting = SiteSetting::first() ?? new SiteSetting();
        $oldPaths = [];
        $newPaths = [];
        try {
            foreach (['logo', 'favicon'] as $field) {
                $upload = $this->{$field.'Upload'};
                if ($upload || $this->{'remove_'.$field}) {
                    $oldPaths[] = $setting->{$field};
                    $data[$field] = $upload ? $upload->store('branding', 'public') : null;
                    if ($upload && !$data[$field]) throw new \RuntimeException('Görsel kaydedilemedi.');
                    if ($data[$field]) $newPaths[] = $data[$field];
                }
            }
            \App\Support\SiteAddress::save($this->site_url, fn () =>
                \Illuminate\Support\Facades\DB::transaction(fn () => $setting->fill($data)->save())
            );
        } catch (\Throwable $e) {
            foreach ($newPaths as $path) Storage::disk('public')->delete($path);
            throw $e;
        }
        foreach ($oldPaths as $path) {
            if ($path && preg_match('~^branding/[a-zA-Z0-9_-]+\.(png|jpe?g|webp|ico)$~', $path)) app(\App\Support\MediaLibrary::class)->cleanup($path);
        }
        $this->reset('logoUpload', 'faviconUpload', 'remove_logo', 'remove_favicon');

        session()->flash('success', 'Sistem ayarları başarıyla kaydedildi.');
    }

    public function render()
    {
        return view('livewire.admin.settings', ['branding' => SiteSetting::first()])
            ->layout('layouts.admin', ['page_title' => 'Sistem Ayarları']);
    }
}
