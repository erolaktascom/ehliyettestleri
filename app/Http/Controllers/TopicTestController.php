<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use App\Models\TopicCategory;
use App\Models\TopicTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TopicTestController extends Controller
{
    public function categories()
    {
        $categories = TopicCategory::where('is_active', true)->whereHas('topicTests', fn ($q) => $q->where('section', 'topic_tests')->where('is_published', true))->withCount(['topicTests' => fn ($query) => $query->where('section', 'topic_tests')->where('is_published', true)])->orderBy('order')->get();

        $totalStudyCount = (int) $categories->sum('topic_tests_count');
        $completedStudyCount = Auth::check()
            ? ExamAttempt::where('user_id', Auth::id())->where('status', 'completed')
                ->whereIn('topic_test_id', TopicTest::where('section', 'topic_tests')->where('is_published', true)
                    ->whereIn('topic_category_id', $categories->modelKeys())->select('id'))
                ->distinct()->count('topic_test_id')
            : 0;

        return view('pages.topic-tests.categories', compact('categories', 'totalStudyCount', 'completedStudyCount'));
    }

    public function category(TopicCategory $category)
    {
        abort_unless($category->is_active, 404);
        $tests = $category->topicTests()->where('section', 'topic_tests')->where('is_published', true)->orderBy('order')->get();

        $completedStudyCount = 0;
        $userAttemptsByTest = collect();
        if (Auth::check()) {
            $userAttempts = ExamAttempt::where('user_id', Auth::id())
                ->whereIn('topic_test_id', $tests->pluck('id'))
                ->orderBy('id', 'desc')
                ->get();

            $completedStudyCount = $userAttempts->where('status', 'completed')->unique('topic_test_id')->count();

            foreach ($tests as $test) {
                $testAttempts = $userAttempts->where('topic_test_id', $test->id);
                $activeAttempt = $testAttempts->firstWhere('status', 'in_progress');

                if ($activeAttempt) {
                    $userAttemptsByTest->put($test->id, ['status' => 'in_progress', 'attempt' => $activeAttempt]);
                    continue;
                }

                $latestCompleted = $testAttempts->firstWhere('status', 'completed');
                if ($latestCompleted) {
                    $userAttemptsByTest->put($test->id, [
                        'status' => 'completed',
                        'attempt' => $latestCompleted,
                    ]);
                } else {
                    $userAttemptsByTest->put($test->id, [
                        'status' => 'not_started',
                        'attempt' => null,
                    ]);
                }
            }
        }

        return view('pages.topic-tests.index', compact('category', 'tests', 'userAttemptsByTest', 'completedStudyCount'));
    }

    public function animated()
    {
        $tests = TopicTest::with('category')->where('section', 'animated')->where('is_published', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))->orderBy('order')->get();

        $completedStudyCount = 0;
        $userAttemptsByTest = collect();
        if (Auth::check() && $tests->isNotEmpty()) {
            $userAttempts = ExamAttempt::where('user_id', Auth::id())
                ->whereIn('topic_test_id', $tests->pluck('id'))
                ->orderBy('id', 'desc')
                ->get();

            $completedStudyCount = $userAttempts->where('status', 'completed')->unique('topic_test_id')->count();

            foreach ($tests as $test) {
                $testAttempts = $userAttempts->where('topic_test_id', $test->id);
                $activeAttempt = $testAttempts->firstWhere('status', 'in_progress');

                if ($activeAttempt) {
                    $userAttemptsByTest->put($test->id, ['status' => 'in_progress', 'attempt' => $activeAttempt]);
                    continue;
                }

                $latestCompleted = $testAttempts->firstWhere('status', 'completed');
                if ($latestCompleted) {
                    $userAttemptsByTest->put($test->id, [
                        'status' => 'completed',
                        'attempt' => $latestCompleted,
                    ]);
                } else {
                    $userAttemptsByTest->put($test->id, [
                        'status' => 'not_started',
                        'attempt' => null,
                    ]);
                }
            }
        }

        return view('pages.animated-questions.index', compact('tests', 'userAttemptsByTest', 'completedStudyCount'));
    }

    public function start(TopicTest $test, Request $request)
    {
        abort_unless($test->is_published && $test->category?->is_active, 404);

        $questions = $test->questions()->where('questions.status', 'Yayında')->get();
        if ($questions->isEmpty()) {
            return redirect($test->listUrl())->with('error', 'Bu testte yayında soru bulunmuyor.');
        }

        if (!$request->user()) return redirect($test->publicUrl());

        $active = ExamAttempt::where('user_id', $request->user()->id)->where('topic_test_id', $test->id)->where('status', 'in_progress')->latest('id')->first();
        if ($active) return redirect($active->playUrl());
        $now = now();
        // Konu testlerinde süre sınırı yok (Süresiz çalışma modu)
        $expiresAt = (clone $now)->addYears(1);

        $attributes = [
            'user_id' => Auth::id(),
            'topic_test_id' => $test->id,
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
        abort_unless($attempt->topic_test_id, 404);
        return $attempt->status === 'in_progress'
            ? redirect($attempt->playUrl())
            : redirect()->route('exams.result', $attempt);
    }

    public function play(TopicCategory $category, string $test, Request $request)
    {
        $test = $category->topicTests()->where('section', 'topic_tests')->where('slug', $test)->firstOrFail();
        return $this->renderTest($test, $request);
    }

    public function animatedPlay(string $test, Request $request)
    {
        $test = TopicTest::where('section', 'animated')->where('slug', $test)->firstOrFail();
        return $this->renderTest($test, $request);
    }


    private function renderTest(TopicTest $test, Request $request)
    {
        $category = $test->category;
        abort_unless($category, 404);
        if (!$request->user()) {
            abort_unless($test->is_published && $category->is_active, 404);
            return GuestPracticeController::play($test);
        }
        $attempt = ExamAttempt::forVisitor($request)->where('topic_test_id', $test->id)->where('status', 'in_progress')->latest('id')->first();
        if (!$attempt) {
            abort_unless($test->is_published && $category->is_active, 404);
            return $this->start($test, $request)->header('Cache-Control', 'private, no-store');
        }
        Gate::authorize('view', $attempt);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('exams.result', $attempt);
        }

        $test = $attempt->topicTest;
        $questions = $attempt->questionQuery()->get();
        $answers = $attempt->answers->keyBy('question_id');
        $remainingSeconds = 0; // Süresiz test
        $savedQuestionIds = Auth::check() ? Auth::user()->savedQuestions()->pluck('question_id')->toArray() : [];

        return response()->view('pages.topic-tests.take', compact('attempt', 'test', 'questions', 'answers', 'remainingSeconds', 'savedQuestionIds'))
            ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
