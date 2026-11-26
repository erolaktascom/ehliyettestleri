<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    protected $fillable = ['robots_txt', 'sitemap_enabled', 'verification_tags', 'content_rules', 'sitemap_sections', 'sitemap_links_per_file'];
    protected $casts = ['sitemap_enabled' => 'boolean', 'verification_tags' => 'array', 'content_rules' => 'array', 'sitemap_sections' => 'array', 'sitemap_links_per_file' => 'integer'];

    public function paginationIndex(): string
    {
        return ($this->content_rules['pagination']['index'] ?? null) === 'noindex' ? 'noindex' : 'index';
    }
}
