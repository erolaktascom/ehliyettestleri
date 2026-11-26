<?php

namespace App\Livewire\Admin;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $members = User::count();
        $newMembers30Days = User::where('created_at', '>=', now()->subDays(30))->count();
        $questions = Question::count();
        $publishedExams = Exam::where('is_published', true)->count();
        $finishedAttempts = ExamAttempt::where('status', 'completed')->count();
        $averageScore = round((float)(ExamAttempt::where('status', 'completed')->avg('score') ?? 0), 1);

        // Difficult questions
        $difficultQuestions = ExamAttemptAnswer::select(
                'question_id',
                DB::raw('count(*) as total_answers'),
                DB::raw('sum(case when is_correct = 0 then 1 else 0 end) as wrong_count')
            )
            ->whereHas('attempt', fn ($query) => $query->where('status', 'completed'))
            ->whereNotNull('selected_option')->where('selected_option', '!=', '')
            ->groupBy('question_id')
            ->havingRaw('wrong_count > 0')
            ->orderByDesc('wrong_count')
            ->limit(8)
            ->with('question')
            ->get()
            ->map(function ($item) {
                $ratio = $item->total_answers > 0 ? round(($item->wrong_count / $item->total_answers) * 100, 1) : 0;
                return (object)[
                    'question_text' => $item->question?->question_text ?? ('Soru #' . $item->question_id),
                    'wrong_count' => $item->wrong_count,
                    'total_answers' => $item->total_answers,
                    'wrong_ratio' => $ratio,
                ];
            });

        // Exam performance
        $examPerformance = Exam::withCount(['attempts as attempts_count' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->withAvg(['attempts as avg_score' => function ($q) {
                $q->where('status', 'completed');
            }], 'score')
            ->withCount(['attempts as passed_count' => function ($q) {
                $q->where('status', 'completed')->where('score', '>=', 70);
            }])
            ->orderByDesc('attempts_count')
            ->limit(6)
            ->get();

        // 14-day activity
        $startDate = now()->subDays(13)->startOfDay();
        $userCounts = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date_val, count(*) as count')
            ->groupBy('date_val')
            ->pluck('count', 'date_val');

        $attemptCounts = ExamAttempt::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date_val, count(*) as count')
            ->groupBy('date_val')
            ->pluck('count', 'date_val');

        $dailyActivity = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $dailyActivity[] = [
                'date' => $date->format('d.m'),
                'member_count' => (int) ($userCounts[$dateStr] ?? 0),
                'attempt_count' => (int) ($attemptCounts[$dateStr] ?? 0),
            ];
        }

        $recentAttempts = ExamAttempt::with(['user', 'exam'])
            ->where('status', 'completed')
            ->latest()
            ->limit(6)
            ->get();

        return view('livewire.admin.dashboard', [
            'members' => $members,
            'newMembers30Days' => $newMembers30Days,
            'questions' => $questions,
            'publishedExams' => $publishedExams,
            'finishedAttempts' => $finishedAttempts,
            'averageScore' => $averageScore,
            'difficultQuestions' => $difficultQuestions,
            'examPerformance' => $examPerformance,
            'dailyActivity' => $dailyActivity,
            'recentAttempts' => $recentAttempts,
        ])->layout('layouts.admin', ['page_title' => 'Dashboard & Genel Bakış']);
    }
}
