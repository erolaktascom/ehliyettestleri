<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'current_question_index',
        'user_id',
        'exam_id',
        'topic_test_id',
        'session_id',
        'question_ids',
        'started_at',
        'expires_at',
        'completed_at',
        'total_questions',
        'correct_count',
        'incorrect_count',
        'empty_count',
        'score',
        'is_passed',
        'status',
    ];

    protected $casts = [
        'current_question_index' => 'integer',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_passed' => 'boolean',
        'score' => 'float',
        'question_ids' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ExamAttempt $attempt) {
            if ($attempt->question_ids !== null || $attempt->exam?->slug === 'yanlis-sorular-tekrar-testi') return;
            $parent = $attempt->topic_test_id ? $attempt->topicTest : $attempt->exam;
            $attempt->question_ids = $parent ? $parent->questions()->where('questions.status', 'Yayında')->pluck('questions.id')->all() : [];
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function topicTest(): BelongsTo
    {
        return $this->belongsTo(TopicTest::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class);
    }

    public function playUrl(): string
    {
        if ($this->topic_test_id) {
            return $this->topicTest?->category
                ? $this->topicTest->publicUrl()
                : route('topic-tests.categories');
        }
        return $this->exam ? route('exams.play', ['exam' => $this->exam->slug]) : route('exams.index');
    }

    public function scopeForVisitor(Builder $query, \Illuminate\Http\Request $request): Builder
    {
        return $request->user()
            ? $query->where('user_id', $request->user()->id)
            : $query->whereRaw('1 = 0');
    }

    public function questionQuery(): Builder
    {
        $ids = $this->question_ids;
        if ($ids === null && $this->exam?->slug === 'yanlis-sorular-tekrar-testi') {
            $ids = $this->answers()->orderBy('id')->pluck('question_id')->all();
        }
        $ids = array_values(array_unique(array_map('intval', $ids ?? [])));
        $query = Question::query()->whereIn('id', $ids);
        if ($ids) {
            $cases = [];
            foreach ($ids as $position => $id) $cases[] = "WHEN {$id} THEN {$position}";
            $query->orderByRaw('CASE id '.implode(' ', $cases).' END');
        }
        return $query;
    }

    public function resultAttributes(\Illuminate\Support\Collection $questions, \Illuminate\Support\Collection $answers): array
    {
        $totalQuestions = $questions->count();

        $correctCount = 0;
        $incorrectCount = 0;
        $emptyCount = 0;

        foreach ($questions as $q) {
            if (!isset($answers[$q->id]) || empty($answers[$q->id]->selected_option)) {
                $emptyCount++;
            } else {
                if (strtoupper($answers[$q->id]->selected_option) === strtoupper($q->correct_option)) {
                    $correctCount++;
                } else {
                    $incorrectCount++;
                }
            }
        }

        // Each question carries equal weight out of 100
        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0;
        $passingScore = $this->exam?->passing_score ?? 70;
        $isPassed = ($score >= $passingScore);

        return [
            'completed_at' => now(),
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'empty_count' => $emptyCount,
            'score' => $score,
            'is_passed' => $isPassed,
            'status' => 'completed',
        ];
    }
}
