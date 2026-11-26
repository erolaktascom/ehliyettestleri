<?php
namespace App\Support;

use App\Models\{ExamAttemptAnswer,User};
use Illuminate\Database\Eloquent\Builder;

class WrongQuestionPool
{
    public static function pending(User $user): Builder
    {
        // Completion makes an answer eligible; answer time (then ID) determines which answer is latest.
        return ExamAttemptAnswer::whereHas('attempt', fn($q)=>$q->where('user_id',$user->id)->where('status','completed'))
            ->whereNotNull('selected_option')->where('selected_option','!=','')->where('is_correct',false)
            ->whereHas('question', fn($q)=>$q->where('status','Yayında'))
            ->whereNotExists(function ($q) use ($user) {
                $q->selectRaw('1')->from('exam_attempt_answers as newer')
                    ->join('exam_attempts as newer_attempt','newer_attempt.id','=','newer.exam_attempt_id')
                    ->whereColumn('newer.question_id','exam_attempt_answers.question_id')
                    ->where('newer_attempt.user_id',$user->id)->where('newer_attempt.status','completed')
                    ->whereNotNull('newer.selected_option')->where('newer.selected_option','!=','')
                    ->where(function ($q) {
                        $new="COALESCE(newer.updated_at,newer.created_at,'')";
                        $old="COALESCE(exam_attempt_answers.updated_at,exam_attempt_answers.created_at,'')";
                        $q->whereRaw("$new > $old")->orWhere(fn($tie)=>$tie->whereRaw("$new = $old")->whereColumn('newer.id','>','exam_attempt_answers.id'));
                    });
            });
    }
}
