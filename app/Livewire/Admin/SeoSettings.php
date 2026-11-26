<?php
namespace App\Livewire\Admin;

use App\Models\SeoSetting;
use App\Support\SeoTemplates;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SeoSettings extends Component
{
    public array $content_rules = [];
    public string $pagination_index = 'index';
    public string $section = 'rules';
    public string $selectedType = 'blog_posts';

    public function mount(): void
    {
        $setting = SeoSetting::first();
        $this->content_rules = SeoTemplates::values($setting?->content_rules);
        $this->pagination_index = $setting?->paginationIndex() ?? 'index';
    }

    public function rules(): array
    {
        $rules = [];
        $template = function ($attribute, $value, $fail) {
            if (!SeoTemplates::validTemplate((string) $value)) $fail('Desteklenmeyen değişken var. Kullanılabilir değişkenler: '.implode(', ', SeoTemplates::TOKENS));
        };
        foreach (SeoTemplates::TYPES as $type => $label) {
            $rules["content_rules.$type"] = 'required|array:title,description,index';
            $rules["content_rules.$type.title"] = ['nullable', 'string', 'max:255', $template];
            $rules["content_rules.$type.description"] = ['nullable', 'string', 'max:1000', $template];
            $rules["content_rules.$type.index"] = 'required|in:index,noindex';
        }
        return [
            ...$rules,
            'pagination_index' => 'required|in:index,noindex',
            'content_rules' => 'required|array:'.implode(',', array_keys(SeoTemplates::TYPES)),
        ];
    }

    public function save(): void
    {
        try {
            $validated = $this->validate();
        } catch (ValidationException $exception) {
            $field = array_key_first($exception->errors());
            $this->section = $field === 'pagination_index' ? 'pagination' : 'rules';
            $type = explode('.', $field)[1] ?? '';
            if (isset(SeoTemplates::TYPES[$type])) $this->selectedType = $type;
            throw $exception;
        }
        $rules = SeoTemplates::values($validated['content_rules']);
        $rules['pagination'] = ['index' => $validated['pagination_index']];
        $setting = SeoSetting::first();
        if ($setting) $setting->update(['content_rules' => $rules]);
        else SeoSetting::create(['content_rules' => $rules]);
        session()->flash('success', 'SEO ayarları başarıyla kaydedildi.');
    }

    public function render()
    {
        $type = isset(SeoTemplates::TYPES[$this->selectedType]) ? $this->selectedType : 'blog_posts';
        return view('livewire.admin.seo-settings', [
            'seoTypes' => SeoTemplates::TYPES,
            'type' => $type,
            'preview' => $this->section === 'pagination' ? null : SeoTemplates::preview(
                $type, SeoTemplates::values($this->content_rules)[$type], \App\Support\SiteBrand::name()
            ),
        ])->layout('layouts.admin', ['title' => 'SEO Ayarları']);
    }
}
