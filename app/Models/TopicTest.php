<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TopicTest extends Model
{
    use \App\Models\Concerns\HasSeo;
    use \App\Models\Concerns\TracksSlugRedirects;
    use \App\Models\Concerns\TracksCreator;
    use \App\Models\Concerns\TracksPublication;

    public const SECTIONS = [
        'topic_tests' => 'Konu Testleri',
        'animated' => 'Animasyonlu Sorular',
    ];

    protected $attributes = ['section' => 'topic_tests'];

    // Database-computed uniqueness keys must never be copied into INSERT statements.
    public function replicate(?array $except = null)
    {
        return parent::replicate(array_merge($except ?? [], ['category_slug_scope', 'animated_slug_scope']));
    }


    public function publicUrl(): string
    {
        return url(\App\Support\AutomaticSlugRedirects::publicPath($this) ?? '/konu-testleri');
    }

    public function sectionLabel(): string
    {
        return self::SECTIONS[$this->section] ?? self::SECTIONS['topic_tests'];
    }

    public function listUrl(): string
    {
        return match ($this->section) {
            'animated' => route('animated-questions.index'),
            default => $this->category ? route('topic-tests.category', $this->category) : route('topic-tests.categories'),
        };
    }

    protected $fillable = [
        'published_at',
        'topic_category_id',
        'section',
        'title',
        'slug',
        'description',
        'is_published',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TopicCategory::class, 'topic_category_id');
    }

    public function getQuestionCountAttribute(): int
    {
        return $this->attributes['questions_count'] ?? ($this->relationLoaded('questions')
            ? $this->questions->count() : $this->questions()->count());
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_topic_test')->withPivot('position')->orderByPivot('position')->orderBy('questions.id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
