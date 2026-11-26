<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    use \App\Models\Concerns\HasSeo;
    use \App\Models\Concerns\TracksSlugRedirects;
    use \App\Models\Concerns\TracksCreator;
    use \App\Models\Concerns\TracksPublication;

    protected $fillable = [
        'blog_category_id',
        'title',
        'slug',
        'summary',
        'featured_image_path',
        'content',
        'author_name',
        'views_count',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleted(fn (BlogPost $post) => static::deleteUnusedFeaturedImage($post->featured_image_path));
        static::saving(function (BlogPost $post) {
            // Run before publication tracking so a forged author does not count
            // as an edit. Legacy imports without a known creator retain their name.
            if ($post->exists) {
                $post->author_name = $post->getOriginal('creator_name') ?: $post->getOriginal('author_name');
            } else {
                $user = auth()->user();
                if ($user && ($user->isAdministrator() || $user->isEditor())) {
                    $post->author_name = $user->name;
                }
            }
        });
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image_path && preg_match('~^blog-images/[a-zA-Z0-9_-]+\.(png|jpe?g|webp)$~', $this->featured_image_path)
            ? asset('storage/'.$this->featured_image_path) : null;
    }

    public static function deleteUnusedFeaturedImage(?string $path): void
    {
        if ($path && preg_match('~^blog-images/[a-zA-Z0-9_-]+\.(png|jpe?g|webp)$~', $path)
            && !static::where('featured_image_path', $path)->exists()
            && !static::where('content', 'like', '%'.$path.'%')->exists()) {
            app(\App\Support\MediaLibrary::class)->cleanup($path);
        }
    }

    public function isScheduled(): bool
    {
        return $this->is_published && $this->published_at && $this->published_at->isFuture();
    }

    public function isContentPublished(): bool
    {
        return $this->is_published && (!$this->published_at || $this->published_at->lte(now()));
    }

    public static function syncPublicationDates(): void
    {
        // Visibility is time-based and does not require a cron worker.
        \Illuminate\Support\Facades\DB::table('blog_posts')->where('is_published',true)
            ->whereNull('first_published_at')->whereNotNull('published_at')->where('published_at','<=',now())
            ->update(['first_published_at'=>\Illuminate\Support\Facades\DB::raw('published_at')]);
    }

    public function scopeVisible($query)
    {
        static::syncPublicationDates();
        return $query->where('is_published',true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at','<=',now()));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function getAuthorUrlAttribute(): string
    {
        return route('blog.author', $this->creator?->profile_slug ?: \App\Support\AuthorProfiles::legacySlug((string) $this->author_name));
    }

    public function getAuthorInitialsAttribute(): string
    {
        return User::make(['name' => $this->author_name])->initials;
    }
}
