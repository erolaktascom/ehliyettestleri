<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use \App\Models\Concerns\TracksCreator;
    use \App\Models\Concerns\TracksPublication;

    protected $fillable = [
        'published_at',
        'topic_category_id',
        'question_text',
        'type',
        'status',
        'options',
        'correct_option',
        'explanation',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Get the dynamic list of options (A, B, C, D, E...) as a key => text array.
     */
    public function getOptionsListAttribute(): array
    {
        if (!empty($this->options) && is_array($this->options)) {
            $result = [];
            foreach ($this->options as $index => $opt) {
                if (is_array($opt)) {
                    $key = strtoupper($opt['key'] ?? (is_string($index) ? $index : chr(65 + $index)));
                    $result[$key] = $opt['text'] ?? '';
                } else {
                    $key = is_string($index) ? strtoupper($index) : chr(65 + $index);
                    $result[$key] = (string) $opt;
                }
            }
            return $result;
        }

        return [];
    }

    public function exams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_question')->withPivot('position');
    }

    public function topicTests(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(TopicTest::class, 'question_topic_test')->withPivot('position');
    }



    public function topicCategory(): BelongsTo
    {
        return $this->belongsTo(TopicCategory::class);
    }

}
