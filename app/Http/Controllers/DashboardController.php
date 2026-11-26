<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Support\WrongQuestionPool;
use App\Support\PerformanceReport;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\Lecture;
use App\Models\Question;
use App\Models\TopicCategory;
use App\Models\TrafficSign;
use App\Models\UserLectureProgress;
use App\Models\UserSignReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $quickExam = Exam::where('is_published', true)
            ->whereHas('questions', fn ($query) => $query->where('questions.status', 'Yayında'))
            ->orderBy('order')->orderBy('id')->first();

        // 1. Total questions in system
        $totalQuestions = Question::count();

        // 2. Unique questions answered correctly in official exams or topic tests (Readiness metric)
        $correctQuestionIds = ExamAttemptAnswer::whereHas('attempt', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('status', 'completed')
                ->where(function ($sq) {
                    $sq->whereHas('exam', function ($eq) {
                        $eq->where('slug', '!=', 'yanlis-sorular-tekrar-testi');
                    })->orWhereNotNull('topic_test_id');
                });
        })->where('is_correct', true)->distinct()->pluck('question_id');

        $uniqueCorrectCount = $correctQuestionIds->count();
        $prepPercent = $totalQuestions > 0 ? round(($uniqueCorrectCount / $totalQuestions) * 100) : 0;

        // 4. Deneme exams summary (official exams)
        $completedAttempts = $user->attempts()
            ->where('status', 'completed')
            ->where(function ($sq) {
                $sq->whereHas('exam', function ($eq) {
                    $eq->where('slug', '!=', 'yanlis-sorular-tekrar-testi');
                })->orWhereNotNull('topic_test_id');
            })
            ->latest()
            ->get();
        $recentAttempts = $completedAttempts->take(3)->load(['exam', 'topicTest']);
        $completedExamsCount = $completedAttempts->count();
        $last3Avg = $completedExamsCount > 0 ? round($completedAttempts->take(3)->avg('score'), 1) : 0.0;

        // Unique questions answered (any attempt)
        $answeredQuestionIds = ExamAttemptAnswer::whereNotNull('selected_option')->whereHas('attempt', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('status', '!=', 'abandoned');
        })->distinct()->pluck('question_id');
        $answeredUniqueCount = $answeredQuestionIds->count();

        // Only the latest answered outcome in completed sessions determines the pool.
        $unresolvedWrongCount = WrongQuestionPool::pending($user)->count();

        // 6. Topic Categories breakdown
        $topicCategories = TopicCategory::where('is_active', true)->orderBy('order')->get();
        $categoryStats = [];
        $weakCategories = [];

        foreach ($topicCategories as $cat) {
            $categoryQuestions = Question::where(function ($query) use ($cat) {
                $query->where('topic_category_id', $cat->id)
                    ->orWhere(function ($fallback) use ($cat) {
                        $fallback->whereNull('topic_category_id')
                            ->whereHas('topicTests', fn ($test) => $test->where('topic_category_id', $cat->id));
                    });
            });
            $catQuestionCount = (clone $categoryQuestions)->count();
            $catCorrectCount = (clone $categoryQuestions)->whereIn('id', $correctQuestionIds)->count();
            $percent = $catQuestionCount > 0 ? round(($catCorrectCount / $catQuestionCount) * 100) : 0;

            $categoryStats[] = [
                'category' => $cat,
                'total_questions' => $catQuestionCount,
                'correct_questions' => $catCorrectCount,
                'percent' => $percent,
            ];

            if ($catQuestionCount > 0 && $percent < 50) {
                $weakCategories[] = $cat->name;
            }
        }

        // 7. Learning areas (Traffic signs & Lectures)
        $totalSignsCount = TrafficSign::published()->count();
        $knownSignsCount = UserSignReview::where('user_id', $user->id)
            ->whereHas('sign', fn ($q) => $q->published())
            ->orderByDesc('updated_at')->orderByDesc('id')->get()
            ->unique('traffic_sign_id')->where('status', 'known')->count();
        $signsPercent = $totalSignsCount > 0 ? round(($knownSignsCount / $totalSignsCount) * 100) : 0;

        $totalLecturesCount = Lecture::where('is_published', true)->count();
        $completedLecturesCount = UserLectureProgress::where('user_id', $user->id)
            ->whereIn('lecture_id', Lecture::where('is_published', true)->select('id'))
            ->where('is_completed', true)
            ->count();
        $lecturesPercent = $totalLecturesCount > 0 ? round(($completedLecturesCount / $totalLecturesCount) * 100) : 0;

        // 8. Saved questions count
        $savedQuestionsCount = $user->savedQuestions()->count();

        // 9. Weak categories summary string
        $weakSummary = !empty($weakCategories)
            ? implode(', ', array_slice($weakCategories, 0, 2)) . ' alanlarında ek çalışma öneriliyor.'
            : 'Tüm alanlarda başarılı bir ilerleme kaydediyorsunuz.';

        return view('pages.student.dashboard', compact(
            'user',
            'quickExam',
            'prepPercent',
            'uniqueCorrectCount',
            'totalQuestions',
            'completedExamsCount',
            'recentAttempts',
            'last3Avg',
            'answeredUniqueCount',
            'unresolvedWrongCount',
            'categoryStats',
            'totalSignsCount',
            'knownSignsCount',
            'signsPercent',
            'totalLecturesCount',
            'completedLecturesCount',
            'lecturesPercent',
            'savedQuestionsCount',
            'weakSummary'
        ));
    }

    public function profileSettings()
    {
        $user = Auth::user();
        return view('pages.student.settings-profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'target_license_class' => 'nullable|string|max:50',
            'driving_exam_date' => 'nullable|date',
            'city' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before_or_equal:today',
            'biography' => 'nullable|string|max:1500',
            'profile_photo' => \App\Support\ProfilePhoto::RULES,
            'remove_photo' => 'nullable|boolean',
        ], [
            'birth_date.before_or_equal' => 'Doğum tarihi bugünden ileri bir tarih olamaz.',
        ]);

        unset($validated['profile_photo'], $validated['remove_photo']);
        $user->update($validated);
        \App\Support\ProfilePhoto::save($user, $request->file('profile_photo'), $request->boolean('remove_photo'));

        return back()->with('success', 'Profil bilgileriniz başarıyla güncellendi.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'current_password' => $user->password ? 'required|current_password' : 'nullable',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Şifreniz başarıyla güncellendi.');
    }

    public function detailedPerformance(Request $request)
    {
        $user = Auth::user();
        if (!$user->isPremium()) {
            return redirect()->route('student.premium')->with('premium_notice', 'Detaylı gelişim raporu Premium üyeliğe dahildir.');
        }
        $filters = $request->validate([
            'range' => 'sometimes|required|in:all,30,90',
            'kind' => 'sometimes|required|in:all,exams,topic_tests',
            'page' => 'sometimes|required|integer|min:1',
        ]);
        return view('pages.student.detailed-performance', ['user' => $user] + PerformanceReport::build(
            $user, $filters['range'] ?? 'all', $filters['kind'] ?? 'all'
        ));
    }

    public function savedQuestions()
    {
        $user = Auth::user();
        $savedQuestions = $user->savedQuestions()->whereHas('question', fn ($query) => $query->where('status', 'Yayında'))->with('question')->latest()->get();

        return view('pages.student.saved-questions', compact('user', 'savedQuestions'));
    }

    public function premium()
    {
        $user = Auth::user();
        return view('pages.student.premium', compact('user'));
    }

    public function startWrongQuestionsExam()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->isPremium()) {
            return redirect()->route('student.premium')->with('premium_notice', 'Yanlışlardan tekrar testi Premium üyeliğe dahildir.');
        }
        $pendingWrongQuestionIds = WrongQuestionPool::pending($user)
            ->orderByDesc('updated_at')->orderByDesc('id')->limit(50)->pluck('question_id');

        if ($pendingWrongQuestionIds->isEmpty()) {
            return redirect()->route('dashboard')->with('success', 'Tebrikler! Tekrar edilecek çözülmemiş hatalı sorunuz bulunmuyor.');
        }

        $wrongExam = Exam::firstOrCreate(
            ['slug' => 'yanlis-sorular-tekrar-testi'],
            [
                'title' => 'Hatalı Sorular Tekrar Denemesi',
                'duration_minutes' => 30,
                'is_published' => false,
                'order' => 99,
            ]
        );

        if ($wrongExam->is_published) {
            $wrongExam->update(['is_published' => false]);
        }

        $selectedWrongIds = $pendingWrongQuestionIds->take(50);

        $now = now();
        $duration = $wrongExam->duration_minutes ?? 30;
        $expiresAt = (clone $now)->addMinutes($duration);

        $attempt = ExamAttempt::create([
            'user_id' => $user->id,
            'exam_id' => $wrongExam->id,
            'session_id' => session()->getId(),
            'started_at' => $now,
            'expires_at' => $expiresAt,
            'total_questions' => $selectedWrongIds->count(),
            'question_ids' => $selectedWrongIds->values()->all(),
            'status' => 'in_progress',
        ]);

        foreach ($selectedWrongIds as $qId) {
            ExamAttemptAnswer::create([
                'exam_attempt_id' => $attempt->id,
                'question_id' => $qId,
                'selected_option' => null,
                'is_correct' => false,
            ]);
        }

        return redirect($attempt->playUrl());
    }

    public function toggleSaveQuestion(Question $question, Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Giriş yapmalısınız.'], 401);
        }

        $validated = $request->validate(['saved' => 'sometimes|required|boolean']);
        $saved = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $question, $validated) {
            \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $query = \App\Models\SavedQuestion::where('user_id', $user->id)->where('question_id', $question->id);
            $desired = $validated['saved'] ?? !$query->exists();
            if ($desired) {
                abort_unless($question->status === 'Yayında', 404);
                \App\Models\SavedQuestion::firstOrCreate(['user_id' => $user->id, 'question_id' => $question->id]);
            } else {
                $query->delete();
            }
            return (bool) $desired;
        });

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'total_saved' => $user->savedQuestions()->count(),
        ]);
    }
}
