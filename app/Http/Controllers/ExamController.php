<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::where('is_published', true)->withCount(['questions as published_questions_count' => fn ($query) => $query->where('questions.status', 'Yayında')])->orderBy('order')->get();

        $userAttemptsByExam = collect();
        if (Auth::check()) {
            $userAttempts = ExamAttempt::where('user_id', Auth::id())
                ->whereIn('exam_id', $exams->pluck('id'))
                ->orderBy('id', 'desc')
                ->get();

            foreach ($exams as $exam) {
                $examAttempts = $userAttempts->where('exam_id', $exam->id);
                $activeAttempt = $examAttempts->firstWhere('status', 'in_progress');

                if ($activeAttempt) {
                    ExamAttempt::whereKey($activeAttempt->id)->where('status', 'in_progress')->update(['status' => 'abandoned']);
                }

                $latestCompleted = $examAttempts->firstWhere('status', 'completed');
                if ($latestCompleted) {
                    $userAttemptsByExam->put($exam->id, [
                        'status' => 'completed',
                        'attempt' => $latestCompleted,
                    ]);
                } else {
                    $userAttemptsByExam->put($exam->id, [
                        'status' => 'not_started',
                        'attempt' => null,
                    ]);
                }
            }
        }

        return view('pages.exams.index', compact('exams', 'userAttemptsByExam'));
    }
    public function start(Exam $exam, Request $request)
    {
        abort_unless($exam->is_published, 404);

        // Misafir kullanıcılar yalnızca ilk 3 denemeyi (Seviye 1, 2, 3) çözebilir
        if (!Auth::check() && $exam->order > 3) {
            return redirect()->route('register')->with('error', 'Seviye 4 ve üzeri deneme sınavlarını çözebilmek için lütfen ücretsiz üye olun.');
        }

        $questions = $exam->questions()->where('questions.status', 'Yayında')->get();
        if ($questions->isEmpty()) {
            return redirect()->route('exams.index')->with('error', 'Bu sınavda yayında soru bulunmuyor.');
        }

        if (!$request->user()) return redirect(route('exams.play', $exam));

        // Starting again abandons the previous run without a score or completion.
        $existingActive = ExamAttempt::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->first();
        if ($existingActive) {
            ExamAttempt::whereKey($existingActive->id)->where('status', 'in_progress')->update(['status' => 'abandoned']);
        }

        $now = now();
        $duration = $exam->duration_minutes ?? 45;
        $expiresAt = (clone $now)->addMinutes($duration);

        $attributes = [
            'user_id' => Auth::id(),
            'exam_id' => $exam->id,
            'session_id' => $request->session()->getId(),
            'started_at' => $now,
            'expires_at' => $expiresAt,
            'total_questions' => $questions->count(),
            'question_ids' => $questions->pluck('id')->all(),
            'status' => 'in_progress',
        ];
        $attempt = ExamAttempt::create($attributes);
        session()->flash('new_attempt_id', (string) $attempt->id);

        return redirect($attempt->playUrl());
    }

    public function take(ExamAttempt $attempt)
    {
        Gate::authorize('view', $attempt);
        return $attempt->status === 'in_progress'
            ? redirect($attempt->playUrl())
            : redirect()->route('exams.result', $attempt);
    }

    public function play(Exam $exam, Request $request)
    {
        if (!$request->user()) {
            abort_unless($exam->is_published, 404);
            if ($exam->order > 3) return redirect()->route('register');
            return GuestPracticeController::play($exam);
        }
        $attempt = ExamAttempt::forVisitor($request)->where('exam_id', $exam->id)->where('status', 'in_progress')->latest('id')->first();
        if (!$attempt) {
            abort_unless($exam->is_published, 404);
            return $this->start($exam, $request)->header('Cache-Control', 'private, no-store');
        }
        return $this->renderAttempt($attempt);
    }

    private function renderAttempt(ExamAttempt $attempt)
    {
        Gate::authorize('view', $attempt);
        abort_if($attempt->status === 'abandoned', 404);

        if ($attempt->topic_test_id) {
            return redirect($attempt->playUrl());
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('exams.result', $attempt);
        }

        $exam = $attempt->exam;
        abort_unless($exam, 404);
        $questions = $attempt->questionQuery()->get();
        $answers = $attempt->answers->keyBy('question_id');

        $remainingSeconds = $attempt->expires_at
            ? max(0, (int) now()->diffInSeconds($attempt->expires_at, false))
            : 0;
        $savedQuestionIds = Auth::check() ? Auth::user()->savedQuestions()->pluck('question_id')->toArray() : [];

        return response()->view('pages.exams.take', compact('attempt', 'exam', 'questions', 'answers', 'remainingSeconds', 'savedQuestionIds'))
            ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function saveAnswer(ExamAttempt $attempt, Request $request)
    {
        Gate::authorize('view', $attempt);

        return DB::transaction(function () use ($attempt, $request) {
            $attempt = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($attempt->status !== 'in_progress') {
                return response()->json(['error' => 'Sınav tamamlanmış.'], 403);
            }

            if ($attempt->exam_id && now()->greaterThanOrEqualTo($attempt->expires_at)) {
                $this->finalizeAttempt($attempt);
                return response()->json(['error' => 'Sınav süresi doldu.'], 403);
            }

            $validated = $request->validate([
                'question_id' => 'required|integer',
                'selected_option' => 'required|string|max:2',
            ]);

            $question = $attempt->questionQuery()->find($validated['question_id']);
            if (!$question) {
                throw ValidationException::withMessages(['question_id' => 'Bu soru sınav oturumuna ait değil.']);
            }
            $validated['selected_option'] = strtoupper($validated['selected_option']);
            if (!array_key_exists($validated['selected_option'], $question->options_list)) {
                throw ValidationException::withMessages(['selected_option' => 'Geçerli bir şık seçin.']);
            }
            $isCorrect = (strtoupper($question->correct_option) === strtoupper($validated['selected_option']));

            $answer = ExamAttemptAnswer::updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ],
                [
                    'selected_option' => $validated['selected_option'],
                    'is_correct' => $isCorrect,
                ]
            );

            return response()->json([
                'status' => 'success',
                'answer_id' => $answer->id,
                'question_id' => $question->id,
                'selected' => $validated['selected_option'],
                'answered_count' => $attempt->answers()->whereNotNull('selected_option')->where('selected_option', '!=', '')->count(),
            ]);
        }, 3);
    }

    public function position(ExamAttempt $attempt, Request $request)
    {
        Gate::authorize('view', $attempt);
        $data = $request->validate(['position' => 'required|integer|min:0|max:'.max(0, count($attempt->question_ids ?? []) - 1)]);
        return DB::transaction(function () use ($attempt, $data) {
            $attempt = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            abort_unless($attempt->status === 'in_progress', 403);
            $attempt->update(['current_question_index' => $data['position']]);
            return response()->json(['saved' => true]);
        });
    }

    public function leave(ExamAttempt $attempt)
    {
        Gate::authorize('view', $attempt);
        if ($attempt->exam_id) {
            ExamAttempt::whereKey($attempt->id)->where('status', 'in_progress')->update(['status' => 'abandoned']);
        }
        return response()->json(['left' => true]);
    }

    public function finish(ExamAttempt $attempt, Request $request)
    {
        Gate::authorize('view', $attempt);

        abort_if($attempt->status === 'abandoned', 403);
        if ($attempt->status === 'in_progress') {
            $this->finalizeAttempt($attempt);
        }

        return redirect()->route('exams.result', $attempt);
    }

    public function result(ExamAttempt $attempt)
    {
        Gate::authorize('view', $attempt);

        abort_if($attempt->status === 'abandoned', 404);
        if ($attempt->status === 'in_progress') return redirect($attempt->playUrl());

        $exam = $attempt->exam;
        $questions = $attempt->questionQuery()->get();
        $answers = $attempt->answers->keyBy('question_id');

        $savedQuestionIds = Auth::check() ? Auth::user()->savedQuestions()->pluck('question_id')->toArray() : [];

        return response()->view('pages.exams.result', compact('attempt', 'exam', 'questions', 'answers', 'savedQuestionIds'))
            ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function finalizeAttempt(ExamAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $lockedAttempt = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $attempt->setRawAttributes($lockedAttempt->getAttributes(), true);
            $attempt->unsetRelations();
            if ($attempt->status !== 'in_progress') {
                return;
            }
            $questions = $attempt->questionQuery()->get();
            $attempt->update($attempt->resultAttributes($questions, $attempt->answers->keyBy('question_id')));
        }, 3);
    }
}
