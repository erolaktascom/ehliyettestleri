<?php
namespace App\Livewire\Admin\Concerns;

use App\Models\SeoSetting;
use App\Support\PageSeo;
use App\Support\SeoTemplates;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

trait EditsSeo
{
    public ?string $seo_title = '';
    public ?string $seo_description = '';
    public ?string $seo_canonical = '';
    public string $seo_index = 'inherit';
    #[Locked]
    public ?string $seoType = null;

    protected function loadSeo(?Model $record = null): void
    {
        $this->seo_title = $record?->seo_title ?? '';
        $this->seo_description = $record?->seo_description ?? '';
        $this->seo_canonical = $record?->seo_canonical ?? '';
        $this->seo_index = $record?->seo_index ?? 'inherit';
        $this->seoType = SeoTemplates::type($record, '');
        $this->applyCustomSeoIndex();
    }

    public function updatedSeoTitle(): void
    {
        $this->applyCustomSeoIndex();
    }

    public function updatedSeoDescription(): void
    {
        $this->applyCustomSeoIndex();
    }

    private function applyCustomSeoIndex(): void
    {
        if ($this->seo_index === 'inherit' && (filled($this->seo_title) || filled($this->seo_description))) {
            $this->seo_index = PageSeo::indexValue(null, $this->seoType, SeoSetting::first());
        }
    }

    protected function seoRules(): array
    {
        return [
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:1000',
            'seo_canonical' => ['nullable', 'string', 'max:2048', function ($attribute, $value, $fail) {
                if (!PageSeo::validCanonical($value)) $fail('Canonical adresi http:// veya https:// ile başlayan geçerli bir adres olmalıdır; kullanıcı bilgisi veya # bölümü içermemelidir.');
            }],
            'seo_index' => filled($this->seo_title) || filled($this->seo_description)
                ? 'required|in:index,noindex' : 'required|in:inherit,index,noindex',
        ];
    }
}
