<?php

namespace App\Models;

use App\Support\StudyCategoryIcon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TopicCategory extends Model
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
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function studyIcon(): string
    {
        return StudyCategoryIcon::resolve($this->icon, $this->name);
    }

    public function topicTests(): HasMany
    {
        return $this->hasMany(TopicTest::class)->orderBy('order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
