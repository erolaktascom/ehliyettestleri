@extends('layouts.app')

@section('title', $category->name . ' Testleri - ' . $siteName)

<x-study-catalog-styles />
@section('content')
<section class="study-catalog">
    <div class="site-container">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-muted mb-4 sm:mb-5">
            <a href="{{ route('home') }}" class="hover:text-brand-700">Ana Sayfa</a>
            <span>›</span>
            <a href="{{ route('topic-tests.categories') }}" class="hover:text-brand-700">Konu Testleri</a>
            <span>›</span>
            <span class="font-bold text-ink">{{ $category->name }}</span>
        </nav>

        <!-- Header -->
        <div class="bg-white p-4 sm:p-6 lg:p-8 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">{{ $category->name }} Testleri</h1>
                <p class="text-sm text-muted mt-1 sm:mt-1.5">{{ $category->description }}</p>
            </div>
            <x-study-summary :total="$tests->count()" :completed="$completedStudyCount" />
        </div>

        @php
            $categoryIcon = $category->studyIcon();
        @endphp
        <div class="catalog-grid">
            @forelse($tests as $test)
                @php
                    $attemptData = auth()->check() ? $userAttemptsByTest->get($test->id, ['status' => 'not_started', 'attempt' => null]) : ['status' => 'guest', 'attempt' => null];
                @endphp
                <x-study-catalog-card :item="$test" :icon="$categoryIcon" :status="$attemptData['status']" :attempt="$attemptData['attempt']" />
            @empty
                <p class="catalog-empty">Bu kategoriye ait test bulunamadı.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
