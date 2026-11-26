@extends('layouts.app')
@section('title', 'Ehliyet Deneme Sınavları - ' . $siteName)

<x-study-catalog-styles />

@section('content')
<section class="study-catalog">
    <div class="site-container">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-6 bg-white p-4 sm:p-6 lg:p-8 rounded-2xl border border-slate-200 shadow-sm">
            <div>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-ink">Deneme Sınavları</h1>
                <p class="text-sm sm:text-base text-muted mt-1.5 sm:mt-2 max-w-xl">Gerçek MEB sınavı formatında, süreli ve anında çözümlü denemelerle kendini test et.</p>
            </div>
            <x-study-summary :total="$exams->count()" :completed="$userAttemptsByExam->where('status', 'completed')->count()" total-label="Toplam Deneme" unit="Deneme" />
        </div>

        <div class="catalog-grid">
            @forelse($exams as $index => $exam)
                @php
                    $isLocked = !auth()->check() && $exam->order > 3;
                    $attemptData = auth()->check() ? $userAttemptsByExam->get($exam->id, ['status' => 'not_started', 'attempt' => null]) : ['status' => $isLocked ? 'locked' : 'guest', 'attempt' => null];
                    $examStatus = $attemptData['status'];
                    $userAttempt = $attemptData['attempt'];
                @endphp
                <x-study-catalog-card :item="$exam" icon="exam" :status="$examStatus" :attempt="$userAttempt" :locked="$isLocked" kind="exam" />
            @empty
                <p class="catalog-empty">Henüz yayınlanmış bir deneme sınavı bulunmuyor.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
