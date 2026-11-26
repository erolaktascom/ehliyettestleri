<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Exam extends Model
{
    use \App\Models\Concerns\HasSeo;
    use \App\Models\Concerns\TracksSlugRedirects;
    use \App\Models\Concerns\TracksCreator;
    use \App\Models\Concerns\TracksPublication;

    protected $fillable = [
        'published_at',
        'title',
        'slug',
        'description',
        'duration_minutes',
        'passing_score',
        'order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'order' => 'integer',
    ];

    public function getQuestionCountAttribute(): int
    {
        return $this->attributes['questions_count'] ?? ($this->relationLoaded('questions')
            ? $this->questions->count() : $this->questions()->count());
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_question')->withPivot('position')->orderByPivot('position')->orderBy('questions.id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
