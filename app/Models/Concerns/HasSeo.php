<?php
namespace App\Models\Concerns;

use App\Models\SeoSetting;
use App\Support\PageSeo;
use App\Support\SeoTemplates;

trait HasSeo
{
    protected static function bootHasSeo(): void
    {
        static::saving(function ($record) {
            if ($record->seo_index === 'inherit' && (filled($record->seo_title) || filled($record->seo_description))) {
                $record->seo_index = PageSeo::indexValue(null, SeoTemplates::type($record, ''), SeoSetting::first());
            }
        });
    }

    public function initializeHasSeo(): void
    {
        // Model defaults avoid rebuilding populated SQLite tables and their foreign keys.
        $this->attributes['seo_index'] ??= 'inherit';
        $this->mergeFillable(['seo_title', 'seo_description', 'seo_canonical', 'seo_index']);
    }
}
