<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrafficSignCategory extends Model
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

    public function signs(): HasMany
    {
        return $this->hasMany(TrafficSign::class)->orderBy('order');
    }
}
