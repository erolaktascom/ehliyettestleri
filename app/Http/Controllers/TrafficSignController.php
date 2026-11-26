<?php

namespace App\Http\Controllers;

use App\Models\{TrafficSign, TrafficSignCategory, User, UserSignReview, UserSignLearningProgress};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrafficSignController extends Controller
{
    private function statuses(Request $request, $ids): array
    {
        if (!$request->user()) return [];

        return UserSignReview::where('user_id', $request->user()->id)
            ->whereIn('traffic_sign_id', $ids)->orderBy('updated_at')->orderBy('id')
            ->pluck('status', 'traffic_sign_id')->all();
    }

    public function index(Request $request)
    {
        $categories = TrafficSignCategory::with(['signs' => fn ($q) => $q->published()])
            ->orderBy('order')->orderBy('id')->get();
        $statuses = $this->statuses($request, $categories->flatMap->signs->pluck('id'));
        foreach ($categories as $category) {
            $category->signs_count = $category->signs->count();
            $category->known_count = $category->signs->filter(fn ($sign) => ($statuses[$sign->id] ?? null) === 'known')->count();
            $category->progress_percent = $category->signs_count ? round($category->known_count / $category->signs_count * 100) : 0;
        }
        $totalSignsCount = $categories->sum('signs_count');
        return view('pages.traffic-signs.categories', compact('categories', 'totalSignsCount'));
    }

    public function learn(TrafficSignCategory $category, Request $request)
    {
        $signs = $category->signs()->published()->get();
        $statuses = $this->statuses($request, $signs->pluck('id'));
        $position = $request->user() ? UserSignLearningProgress::where('user_id', $request->user()->id)
            ->where('traffic_sign_category_id', $category->id)->first() : null;

        $learnerConfig = [
            'categoryId' => $category->id,
            'isMember' => (bool) $request->user(),
            'initialStatuses' => $statuses,
            'initialSignId' => $position?->traffic_sign_id,
            'initialFilter' => $position?->filter ?? 'all',
            'positionUrl' => route('traffic-signs.position', $category),
            'csrfToken' => csrf_token(),
            'signs' => $signs->map(fn ($sign) => [
                'id' => $sign->id, 'code' => $sign->code, 'name' => $sign->name,
                'meaning' => $sign->meaning, 'image' => $sign->image_url,
                'reviewUrl' => route('traffic-signs.save-review', $sign),
            ])->values()->all(),
        ];
        return view('pages.traffic-signs.learn', compact('category', 'signs', 'learnerConfig'));
    }

    public function saveReview(TrafficSign $sign, Request $request)
    {
        abort_unless($sign->is_published, 404);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['known', 'review'])],
            'next_sign_id' => ['nullable', 'integer', Rule::exists('traffic_signs', 'id')->where('traffic_sign_category_id', $sign->traffic_sign_category_id)->where('is_published', true)],
            'filter' => ['sometimes', Rule::in(['all', 'unlearned', 'review'])],
        ]);

        DB::transaction(function () use ($request, $sign, $validated) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            UserSignReview::updateOrCreate([
                'user_id' => $request->user()->id, 'traffic_sign_id' => $sign->id,
            ], ['status' => $validated['status'], 'session_id' => null]);
            UserSignLearningProgress::updateOrCreate([
                'user_id' => $request->user()->id, 'traffic_sign_category_id' => $sign->traffic_sign_category_id,
            ], ['traffic_sign_id' => $validated['next_sign_id'] ?? $sign->id, 'filter' => $validated['filter'] ?? 'all']);
        });

        return response()->json(['success' => true, 'status' => $validated['status']]);
    }

    public function position(TrafficSignCategory $category, Request $request)
    {
        $validated = $request->validate([
            'sign_id' => ['nullable', 'integer', Rule::exists('traffic_signs', 'id')->where('traffic_sign_category_id', $category->id)->where('is_published', true)],
            'filter' => ['required', Rule::in(['all', 'unlearned', 'review'])],
        ]);
        DB::transaction(function () use ($category, $request, $validated) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            UserSignLearningProgress::updateOrCreate([
                'user_id' => $request->user()->id, 'traffic_sign_category_id' => $category->id,
            ], ['traffic_sign_id' => $validated['sign_id'] ?? null, 'filter' => $validated['filter']]);
        });
        return response()->json(['success' => true]);
    }
}
