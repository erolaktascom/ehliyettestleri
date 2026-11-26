<?php
namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PerformanceReport
{
    public static function build(User $user, string $range, string $kind): array
    {
        $query=$user->attempts()->where('status','completed')->where(fn($q)=>$q
            ->whereHas('exam',fn($e)=>$e->where('slug','!=','yanlis-sorular-tekrar-testi'))->orWhereNotNull('topic_test_id'));
        if ($range !== 'all') $query->whereRaw('COALESCE(completed_at, started_at) >= ?', [now()->subDays((int)$range)->toDateTimeString()]);
        if ($kind === 'exams') $query->whereNull('topic_test_id');
        if ($kind === 'topic_tests') $query->whereNotNull('topic_test_id');
        $all=(clone $query)->orderByRaw('COALESCE(completed_at, started_at) DESC')->orderByDesc('id')->get();
        $totalAttempts=$all->count();
        $passedAttempts=$all->where('is_passed',true)->count();
        $averageScore=$totalAttempts ? round($all->avg('score'),1) : 0;
        $recentAverage=$totalAttempts ? round($all->take(5)->avg('score'),1) : null;
        $trend=$totalAttempts>=10 ? round($all->take(5)->avg('score')-$all->slice(5,5)->avg('score'),1) : null;
        $chart=$all->take(20)->reverse()->values();
        $categoryPerformance=DB::table('exam_attempt_answers as answers')
            ->join('questions','questions.id','=','answers.question_id')
            ->leftJoin('topic_categories','topic_categories.id','=','questions.topic_category_id')
            ->whereIntegerInRaw('answers.exam_attempt_id',$all->modelKeys())
            ->whereNotNull('answers.selected_option')->where('answers.selected_option','!=','')
            ->groupBy('topic_categories.id','topic_categories.name')
            ->selectRaw("COALESCE(topic_categories.name, 'Kategorisiz') as name, COUNT(*) as answered, SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) as correct")
            ->orderBy('name')->get()->map(function($row){$row->percent=(int)round(100*$row->correct/$row->answered); return $row;});
        $attempts=(clone $query)->with(['exam','topicTest'])->orderByRaw('COALESCE(completed_at, started_at) DESC')->orderByDesc('id')->paginate(15)->withQueryString();
        $correctTotal=(int)$all->sum('correct_count');
        $incorrectTotal=(int)$all->sum('incorrect_count');
        $emptyTotal=(int)$all->sum('empty_count');
        return compact('attempts','totalAttempts','passedAttempts','averageScore','recentAverage','trend','chart','categoryPerformance','correctTotal','incorrectTotal','emptyTotal','range','kind');
    }
}
