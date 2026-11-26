<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use \App\Models\Concerns\HasSeo;
    use \App\Models\Concerns\TracksSlugRedirects;
    use \App\Models\Concerns\TracksCreator;
    use \App\Models\Concerns\TracksPublication;

    protected $fillable = ['title', 'slug', 'short_description', 'content', 'status', 'published_at', 'view_count', 'seo_title', 'seo_description'];
    protected $casts = ['published_at' => 'datetime', 'view_count' => 'integer'];
    protected $attributes = ['status' => 'published', 'seo_index' => 'index'];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if (!$page->exists || $page->isDirty('slug')) \App\Support\PageSlug::validate((string) $page->slug);
        });
    }

    public function isContentPublished(): bool
    {
        return $this->status === 'published';
    }

}
