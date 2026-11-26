<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrafficSign extends Model
{
    use \App\Models\Concerns\TracksCreator;

    protected $fillable = [
        'traffic_sign_category_id',
        'code',
        'name',
        'meaning',
        'image_path',
        'svg_icon',
        'order',
        'is_published',
    ];

    protected $casts = ['is_published' => 'boolean'];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = trim((string) $this->image_path);
        // Old seed data used a generic warning emoji for unrelated signs.
        if ($path === '' || str_contains($path, '/twemoji/') || preg_match('~[\\x00-\\x20]~', $path)) return null;
        if (preg_match('~^https?://~i', $path)) return filter_var($path, FILTER_VALIDATE_URL) ? $path : null;
        if (str_starts_with($path, '//') || str_contains($path, ':') || str_contains($path, '..')) return null;
        if (str_starts_with($path, 'traffic-signs/')) return asset('storage/'.$path);
        return asset(ltrim($path, '/'));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrafficSignCategory::class, 'traffic_sign_category_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(UserSignReview::class);
    }
}
