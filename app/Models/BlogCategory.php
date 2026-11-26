<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    use \App\Models\Concerns\HasSeo;
    use \App\Models\Concerns\TracksSlugRedirects;
    use \App\Models\Concerns\TracksCreator;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }
}
