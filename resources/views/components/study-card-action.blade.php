@props(['item', 'status', 'attempt' => null, 'locked' => false, 'kind' => 'test'])
@php
    $isExam = $kind === 'exam';
    $startUrl = route($isExam ? 'exams.start' : 'topic-tests.start', $item);
    $formId = ($isExam ? 'exam-start-' : 'topic-test-start-') . $item->id;
    $action = $locked ? 'Üye Olun' : ($status === 'completed' ? 'Sonucu ve Çözümleri Gör' : ($status === 'in_progress' ? 'Kaldığın Yerden Devam Et' : ($isExam ? 'Sınavı Başlat' : 'Testi Başlat')));
    $href = $locked ? route('register') : ($status === 'completed' ? route('exams.result', $attempt) : ($status === 'in_progress' ? $attempt->playUrl() : null));
@endphp
@if($href)
    <a class="study-card-action" href="{{ $href }}" aria-label="{{ $item->title }} — {{ $action }}" title="{{ $action }}"></a>
@else
    <form id="{{ $formId }}" method="POST" action="{{ $startUrl }}" class="study-card-start">
        @csrf
        <button type="submit" class="study-card-action" aria-label="{{ $item->title }} — {{ $action }}" title="{{ $action }}"></button>
    </form>
@endif
<div class="study-card-controls">
    @if($status === 'completed' && !$locked)
        <form method="POST" action="{{ $startUrl }}" class="study-card-repeat">
            @csrf
            <button type="submit" class="study-card-repeat-button" aria-label="{{ $item->title }} — {{ $isExam ? 'Sınavı Tekrar Çöz' : 'Testi Tekrar Çöz' }}" title="{{ $isExam ? 'Sınavı Tekrar Çöz' : 'Testi Tekrar Çöz' }}">
                <x-student-icon name="repeat" />
            </button>
        </form>
    @endif
    <span class="study-card-arrow {{ $locked ? 'text-amber-700' : 'text-brand-700' }}" aria-hidden="true"><x-student-icon name="arrow" /></span>
</div>
@once
    @push('styles')
        <style>
            .study-card { position: relative; isolation: isolate; }
            .study-card h3 { white-space: normal; overflow-wrap: anywhere; }
            .study-card-start { display: contents; }
            .study-card-action { position: absolute; inset: 0; z-index: 1; width: 100%; height: 100%; border: 0; border-radius: inherit; background: transparent; cursor: pointer; }
            .study-card-action:focus-visible { outline: 3px solid #516bf0; outline-offset: -4px; }
            .study-card-controls { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
            .study-card-arrow { display: grid; place-items: center; width: 28px; height: 44px; transition: transform .2s; }
            .study-card-arrow svg, .study-card-repeat-button svg { width: 20px; height: 20px; }
            .study-card:hover .study-card-arrow { transform: translateX(3px); }
            .study-card-repeat { position: relative; z-index: 2; }
            .study-card-repeat-button { display: grid; place-items: center; width: 44px; height: 44px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; color: #475569; }
            .study-card-repeat-button:hover { background: #eef2ff; color: #263fe5; }
            .study-card-repeat-button:focus-visible { outline: 3px solid #516bf0; outline-offset: 2px; }
            @media (prefers-reduced-motion: reduce) { .study-card, .study-card-arrow { transition: none; transform: none !important; } }
        </style>
    @endpush
@endonce
