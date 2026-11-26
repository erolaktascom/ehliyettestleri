<?php

namespace App\Http\Controllers;

use App\Models\Lecture;
use App\Models\LectureCategory;
use App\Models\UserLectureProgress;
use Illuminate\Support\Facades\Auth;

class LectureController extends Controller
{
    public function index()
    {
        $categories = LectureCategory::with(['lectures' => fn ($query) => $query->where('is_published', true)])->orderBy('order')->get();
        $userId = Auth::id();

        $completedLectureIds = $userId ? UserLectureProgress::where('is_completed', true)
            ->whereIn('lecture_id', Lecture::where('is_published', true)->select('id'))
            ->where('user_id', $userId)
            ->pluck('lecture_id')
            ->toArray() : [];

        $totalLecturesCount = Lecture::where('is_published', true)->count();
        $totalCompletedCount = count($completedLectureIds);

        foreach ($categories as $cat) {
            $catLecturesCount = $cat->lectures->count();
            $catCompletedCount = $cat->lectures->whereIn('id', $completedLectureIds)->count();
            $cat->completed_count = $catCompletedCount;
            $cat->total_count = $catLecturesCount;
            $cat->progress_percent = $catLecturesCount > 0 ? round(($catCompletedCount / $catLecturesCount) * 100) : 0;
        }

        return view('pages.lectures.index', compact('categories', 'totalLecturesCount', 'totalCompletedCount'));
    }

    public function category(LectureCategory $category)
    {
        $lectures = $category->lectures()->where('is_published', true)->orderBy('order')->orderBy('id')->get();
        $userId = Auth::id();

        $completedIds = $userId ? UserLectureProgress::whereIn('lecture_id', $lectures->pluck('id'))
            ->where('is_completed', true)
            ->where('user_id', $userId)
            ->pluck('lecture_id')
            ->toArray() : [];

        $completedCount = count($completedIds);
        $totalCount = $lectures->count();
        $progressPercent = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

        return view('pages.lectures.category', compact('category', 'lectures', 'completedIds', 'completedCount', 'totalCount', 'progressPercent'));
    }

    public function show(LectureCategory $category, Lecture $lecture)
    {
        abort_unless($lecture->is_published && (int) $lecture->lecture_category_id === (int) $category->id, 404);

        Lecture::withoutTimestamps(fn () => $lecture->incrementQuietly('view_count'));
        $userId = Auth::id();

        $isCompleted = $userId ? UserLectureProgress::where('lecture_id', $lecture->id)
            ->where('is_completed', true)
            ->where('user_id', $userId)
            ->exists() : false;

        // Stable category order: equal sort numbers are resolved by record ID.
        $previousLecture = $category->lectures()->where('is_published', true)
            ->where(fn ($q) => $q->where('order', '<', $lecture->order)
                ->orWhere(fn ($q) => $q->where('order', $lecture->order)->where('id', '<', $lecture->id)))
            ->orderByDesc('order')->orderByDesc('id')->first();
        $nextLecture = $category->lectures()->where('is_published', true)
            ->where(fn ($q) => $q->where('order', '>', $lecture->order)
                ->orWhere(fn ($q) => $q->where('order', $lecture->order)->where('id', '>', $lecture->id)))
            ->orderBy('order')->orderBy('id')->first();
        $readingContent = \App\Support\LectureReadingContent::prepare($lecture->content);

        return view('pages.lectures.show', compact('category', 'lecture', 'isCompleted', 'previousLecture', 'nextLecture', 'readingContent'));
    }

    public function toggleComplete(Lecture $lecture)
    {
        abort_unless($lecture->is_published, 404);

        $userId = Auth::id();

        $progress = UserLectureProgress::firstOrNew([
            'lecture_id' => $lecture->id,
            'user_id' => $userId,
            'session_id' => null,
        ]);

        $progress->is_completed = !$progress->is_completed;
        $progress->save();

        return back()->with('success', $progress->is_completed ? 'Konu tamamlandı olarak işaretlendi.' : 'Tamamlanma işareti kaldırıldı.');
    }
}
