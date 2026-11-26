<?php
namespace App\Livewire\Admin;

use App\Models\SeoSetting;
use App\Support\SiteSitemap;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SitemapSettings extends Component
{
    public bool $sitemap_enabled = true;
    public array $sitemap_sections = [];
    public int $sitemap_links_per_file = 200;
    public ?string $robots_txt = '';

    public function mount(): void
    {
        $sitemap = new SiteSitemap();
        $this->sitemap_enabled = $sitemap->enabled();
        $this->sitemap_sections = $sitemap->sections();
        $this->sitemap_links_per_file = $sitemap->limit();
        $this->robots_txt = SiteSitemap::robotsBody($sitemap->setting?->robots_txt);
    }

    public function save(): void
    {
        $data = $this->validate([
            'sitemap_enabled' => 'required|boolean',
            'sitemap_sections' => 'present|array',
            'sitemap_sections.*' => ['string', 'distinct', Rule::in(array_keys(SiteSitemap::SECTIONS))],
            'sitemap_links_per_file' => 'required|integer|min:1|max:1000',
            'robots_txt' => 'nullable|string|max:10000',
        ]);
        $data['robots_txt'] = SiteSitemap::robotsBody($data['robots_txt']);
        $setting = SeoSetting::first() ?? new SeoSetting();
        $setting->fill($data)->save();
        $this->robots_txt = $data['robots_txt'];
        session()->flash('success', 'Sitemap ayarları kaydedildi. Site haritaları güncellendi.');
    }

    public function render()
    {
        $sitemap = new SiteSitemap();
        return view('livewire.admin.sitemap-settings', [
            'sections' => SiteSitemap::SECTIONS, 'counts' => $sitemap->counts(),
            'savedSections' => $sitemap->sections(), 'savedEnabled' => $sitemap->enabled(),
            'sitemapUrl' => $sitemap->base().'/sitemap_index.xml',
        ])->layout('layouts.admin', ['title' => 'Sitemap Ayarları']);
    }
}
