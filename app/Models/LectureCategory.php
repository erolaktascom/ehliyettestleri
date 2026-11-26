<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LectureCategory extends Model
{
    use \App\Models\Concerns\HasSeo;
    use \App\Models\Concerns\TracksSlugRedirects;
    use \App\Models\Concerns\TracksCreator;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'order',
    ];

    public function lectures(): HasMany
    {
        return $this->hasMany(Lecture::class)->orderBy('order');
    }
}
