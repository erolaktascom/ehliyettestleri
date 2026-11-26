<?php

namespace App\Livewire\Admin;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Reports extends Component
{
    public function render()
    {
        $members = User::count();
        $questions = Question::count();
        $publishedExams = Exam::where('is_published', true)->count();
        $finishedAttempts = ExamAttempt::where('status', 'completed')->count();
        $passedAttempts = ExamAttempt::where('status', 'completed')->where('is_passed', true)->count();
        $averageScore = round((float)(ExamAttempt::where('status', 'completed')->avg('score') ?? 0), 1);
        $passRate = $finishedAttempts > 0 ? round(($passedAttempts / $finishedAttempts) * 100, 1) : 0;

        // Question topics performance breakdown
        $topicStats = DB::table('questions')
            ->leftJoin('topic_categories', 'questions.topic_category_id', '=', 'topic_categories.id')
            ->leftJoin('exam_attempt_answers', 'questions.id', '=', 'exam_attempt_answers.question_id')
            ->join('exam_attempts', 'exam_attempt_answers.exam_attempt_id', '=', 'exam_attempts.id')
            ->where('exam_attempts.status', 'completed')
            ->whereNotNull('exam_attempt_answers.selected_option')
            ->where('exam_attempt_answers.selected_option', '!=', '')
            ->select(
                DB::raw('COALESCE(topic_categories.name, "Genel") as topic_name'),
                DB::raw('count(exam_attempt_answers.id) as total_answers'),
                DB::raw('sum(case when exam_attempt_answers.is_correct = 1 then 1 else 0 end) as correct_answers')
            )
            ->groupBy('topic_name')
            ->havingRaw('total_answers > 0')
            ->get()
            ->map(function ($row) {
                $rate = $row->total_answers > 0 ? round(($row->correct_answers / $row->total_answers) * 100, 1) : 0;
                return (object)[
                    'topic' => $row->topic_name,
                    'total' => $row->total_answers,
                    'correct' => $row->correct_answers,
                    'success_rate' => $rate,
                ];
            });

        // 14 day history
        $startDate = now()->subDays(13)->startOfDay();
        $attemptCounts = ExamAttempt::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date_val, count(*) as count, avg(score) as avg_score')
            ->groupBy('date_val')
            ->get()
            ->keyBy('date_val');

        $dailyStats = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $row = $attemptCounts->get($dateStr);
            $dailyStats[] = [
                'date' => $date->format('d.m.Y'),
                'attempts' => $row ? $row->count : 0,
                'avg_score' => $row ? round($row->avg_score, 1) : 0,
            ];
        }

        return view('livewire.admin.reports', [
            'members' => $members,
            'questions' => $questions,
            'publishedExams' => $publishedExams,
            'finishedAttempts' => $finishedAttempts,
            'averageScore' => $averageScore,
            'passRate' => $passRate,
            'topicStats' => $topicStats,
            'dailyStats' => $dailyStats,
        ])->layout('layouts.admin', ['page_title' => 'Sistem Raporları ve Analizler']);
    }
}
