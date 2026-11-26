@extends('layouts.app')

@section('title', 'Animasyonlu Sorular - ' . $siteName)

<x-study-catalog-styles />
@section('content')
<section class="study-catalog">
    <div class="site-container">

<!-- Header -->
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-ink">Animasyonlu Sorular</h1>
                <p class="text-base text-muted mt-2 max-w-2xl">Trafik durumlarını hareketli görseller üzerinden değerlendir; çözmek istediğin testi seç ve normal konu testi akışıyla ilerle.</p>
            </div>
            <x-study-summary :total="$tests->count()" :completed="$completedStudyCount" />
        </div>

        <div class="catalog-grid">
            @forelse($tests as $test)
                @php
                    $attemptData = auth()->check() ? $userAttemptsByTest->get($test->id, ['status' => 'not_started', 'attempt' => null]) : ['status' => 'guest', 'attempt' => null];
                @endphp
                <x-study-catalog-card :item="$test" :icon="$test->category->studyIcon()" :status="$attemptData['status']" :attempt="$attemptData['attempt']" />
            @empty
                <p class="catalog-empty">Henüz yayınlanmış animasyonlu soru testi bulunmuyor.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
