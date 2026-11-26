<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\TopicTest;
use Illuminate\Http\Request;

class GuestPracticeController extends Controller
{
    public static function play(Exam|TopicTest $content)
    {
        $questions = $content->questions()->where('questions.status', 'Yayında')->get();
        $exam = $content instanceof Exam;
        if ($questions->isEmpty()) {
            return redirect($exam ? route('exams.index') : $content->listUrl())
                ->with('error', 'Bu testte yayında soru bulunmuyor.');
        }

        $remainingSeconds = $exam ? ($content->duration_minutes ?? 45) * 60 : 0;
        return response()->view($exam ? 'pages.exams.take' : 'pages.topic-tests.take', [
            ($exam ? 'exam' : 'test') => $content,
            'questions' => $questions,
            'remainingSeconds' => $remainingSeconds,
            'attempt' => null,
            'gradeUrl' => route($exam ? 'guest.exam-result' : 'guest.topic-result', $content),
        ])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function sessionToken(Request $request)
    {
        abort_if($request->user(), 403);

        return response()->json(['token' => $request->session()->token()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function examResult(Exam $exam, Request $request)
    {
        abort_unless($exam->is_published, 404);
        abort_if($exam->order > 3, 403);
        return $this->grade($exam, $request);
    }

    public function topicResult(TopicTest $test, Request $request)
    {
        abort_unless($test->is_published && $test->category?->is_active, 404);
        return $this->grade($test, $request);
    }

    private function grade(Exam|TopicTest $content, Request $request)
    {
        abort_if($request->user(), 403);
        abort_unless($request->isJson(), 415);
        $questions = $content->questions()->where('questions.status', 'Yayında')->get();
        abort_if($questions->isEmpty() || $request->input('question_ids') !== $questions->modelKeys(), 409);

        $selections = $request->input('answers');
        abort_unless(is_array($selections), 422);
        $byId = $questions->keyBy('id');
        $answers = collect();
        foreach ($selections as $id => $option) {
            $question = $byId->get($id);
            abort_unless($question && is_string($option) && array_key_exists($option, $question->options_list), 422);
            $answers->put($id, new ExamAttemptAnswer([
                'question_id' => $id, 'selected_option' => $option,
                'is_correct' => $option === strtoupper($question->correct_option),
            ]));
        }

        $exam = $content instanceof Exam ? $content : null;
        $attempt = new ExamAttempt;
        $attempt->setRelation('exam', $exam);
        $attempt->setRelation('topicTest', $exam ? null : $content);
        $attempt->fill($attempt->resultAttributes($questions, $answers));
        $html = view('partials.exam-result', compact('attempt', 'exam', 'questions', 'answers'))->render();
        return response()->json(['html' => $html])->header('Cache-Control', 'private, no-store');
    }
}
