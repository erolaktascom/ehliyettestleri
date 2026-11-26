<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lecture extends Model
{
    use \App\Models\Concerns\HasSeo;
    use \App\Models\Concerns\TracksSlugRedirects;
    use \App\Models\Concerns\TracksCreator;
    use \App\Models\Concerns\TracksPublication;

    protected $fillable = [
        'published_at', 'view_count', 'seo_title', 'seo_description',
        'lecture_category_id',
        'title',
        'slug',
        'summary',
        'content',
        'is_published',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer', 'view_count' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LectureCategory::class, 'lecture_category_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserLectureProgress::class);
    }
}
