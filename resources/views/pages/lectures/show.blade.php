@extends('layouts.app')

@section('title', ($lecture->seo_title ?: $lecture->title) . ' - ' . $siteName)
@section('meta_description', $lecture->seo_description ?: ($lecture->summary ?? ''))

@push('styles')
<style>
    .lecture-reading { padding: 20px 0 56px; }
    .lecture-breadcrumb { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 20px; font-size: .75rem; color: #64748b; overflow-wrap: anywhere; }
    .lecture-breadcrumb a:hover { color: #293be7; }
    .lecture-hero { padding: 32px 40px; border-top: 4px solid #293be7; margin-bottom: 24px; }
    .lecture-eyebrow { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 20px; }
    .lecture-status { display: inline-flex; align-items: center; gap: 6px; font-size: .75rem; font-weight: 600; color: #64748b; }
    .lecture-status.is-complete { color: #047857; }
    .lecture-title { color: #0f172a; font-size: clamp(1.75rem, 3.5vw, 2.75rem); font-weight: 800; line-height: 1.2; letter-spacing: -.035em; overflow-wrap: anywhere; }
    .lecture-summary { max-width: 82ch; margin-top: 16px; color: #64748b; font-size: 1rem; line-height: 1.8; overflow-wrap: anywhere; }
    .lecture-reading-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 24px; align-items: start; }
    .lecture-content { min-width: 0; padding: 36px 40px; color: #475569; font-size: 1.0625rem; line-height: 1.9; overflow-wrap: anywhere; }
    .lecture-content :is(h1,h2,h3,h4,h5,h6) { color: #111827; font-weight: 800; line-height: 1.4; margin: 2rem 0 .85rem; letter-spacing: -.025em; }
    .lecture-content :is(h1,h2) { font-size: 1.45rem; }
    .lecture-content h3 { font-size: 1.2rem; }
    .lecture-content :is(h4,h5,h6) { font-size: 1.05rem; }
    .lecture-content p { margin-bottom: 1.15rem; }
    .lecture-content :is(ul,ol) { padding-left: 1.5rem; margin: 1rem 0 1.5rem; }
    .lecture-content ul { list-style: disc; }
    .lecture-content ol { list-style: decimal; }
    .lecture-content li { padding-left: .25rem; margin: .4rem 0; }
    .lecture-content :is(strong,b) { color: #1e293b; font-weight: 700; }
    .lecture-content a { color: #2339d2; text-decoration: underline; text-underline-offset: 3px; }
    .lecture-content blockquote { border-left: 3px solid #4871f4; background: #f0f4ff; padding: 1rem 1.25rem; margin: 1.5rem 0; border-radius: 0 .75rem .75rem 0; }
    .lecture-content blockquote p:last-child { margin-bottom: 0; }
    .lecture-content :is(img,video) { max-width: 100%; height: auto; border-radius: .75rem; margin: 1.5rem auto; }
    .lecture-content iframe { max-width: 100%; width: 100%; aspect-ratio: 16/9; height: auto; border: 0; border-radius: .75rem; }
    .lecture-content table { display: block; width: 100%; max-width: 100%; overflow-x: auto; border-collapse: collapse; margin: 1.5rem 0; }
    .lecture-content :is(th,td) { border: 1px solid #e2e8f0; padding: .65rem .85rem; text-align: left; }
    .lecture-content th { background: #f8fafc; color: #1e293b; }
    .lecture-content pre { overflow-x: auto; background: #f8fafc; padding: 1rem; border-radius: .75rem; margin: 1rem 0; }
    .lecture-content hr { border-color: #e2e8f0; margin: 2rem 0; }
    .lecture-content > :first-child { margin-top: 0; }
    .lecture-content > :last-child { margin-bottom: 0; }
    .lecture-side { display: grid; gap: 20px; min-width: 0; }
    .lecture-side-card { padding: 24px; }
    .lecture-side-icon { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 14px; background: #eef2ff; color: #293be7; margin-bottom: 16px; }
    .lecture-side-icon.is-complete { background: #ecfdf5; color: #047857; }
    .lecture-side-title { font-size: 1rem; font-weight: 800; color: #0f172a; }
    .lecture-side-copy { margin-top: 8px; font-size: .875rem; line-height: 1.7; color: #64748b; }
    .lecture-complete-button { width: 100%; justify-content: center; margin-top: 20px; min-height: 44px; padding: 12px; font-size: .875rem; }
    .lecture-next { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-top: 18px; margin-top: 16px; border-top: 1px solid #edf0f6; color: #293be7; font-weight: 700; font-size: .875rem; line-height: 1.6; }
    .lecture-next:hover { color: #172592; }
    .lecture-toc { margin-bottom: 24px; padding: 18px 24px; }
    .lecture-toc summary { cursor: pointer; color: #0f172a; font-size: .9375rem; font-weight: 700; }
    .lecture-toc nav { display: grid; gap: 10px; margin-top: 16px; }
    .lecture-toc a { color: #293be7; font-size: .875rem; line-height: 1.6; overflow-wrap: anywhere; }
    .lecture-toc a:hover { text-decoration: underline; }
    .lecture-toc a[data-level="h3"] { padding-left: 16px; }
    .lecture-content :is(h2,h3)[id] { scroll-margin-top: 100px; }
    .lecture-back { display: inline-flex; align-items: center; gap: 8px; padding: 4px; font-size: .875rem; font-weight: 600; color: #64748b; }
    .lecture-back:hover { color: #293be7; }
    .lecture-reading :is(a,button):focus-visible { outline: 3px solid #a5b4fc; outline-offset: 4px; }
    @media (max-width: 1023px) {
        .lecture-reading-grid { grid-template-columns: minmax(0, 1fr); }
        .lecture-side { grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; }
    }
    @media (max-width: 639px) {
        .lecture-reading { padding: 16px 0 32px; }
        .lecture-breadcrumb { gap: 6px; margin-bottom: 16px; }
        .lecture-hero { padding: 24px 20px; margin-bottom: 20px; }
        .lecture-eyebrow { margin-bottom: 16px; }
        .lecture-summary { font-size: .9375rem; margin-top: 14px; }
        .lecture-reading-grid { gap: 20px; }
        .lecture-content { padding: 24px 20px; font-size: 1rem; line-height: 1.85; }
        .lecture-side { grid-template-columns: minmax(0, 1fr); }
        .lecture-side-card { padding: 20px; }
    }
</style>
@endpush

@section('content')
<div class="lecture-reading">
    <div class="site-container">
        <nav aria-label="Sayfa yolu" class="lecture-breadcrumb">
            <a href="{{ route('home') }}">Ana Sayfa</a>
            <span aria-hidden="true">›</span>
            <a href="{{ route('lectures.index') }}">Konu Anlatımı</a>
            <span aria-hidden="true">›</span>
            <a href="{{ route('lectures.category', $category) }}">{{ $category->name }}</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page" class="font-semibold text-slate-600">{{ $lecture->title }}</span>
        </nav>

        <header class="site-card lecture-hero">
            <div class="lecture-eyebrow">
                <a href="{{ route('lectures.category', $category) }}" class="stitch-pill hover:bg-brand-100 transition">{{ $category->name }}</a>
                <span class="lecture-status {{ $isCompleted ? 'is-complete' : '' }}">
                    <x-student-icon :name="$isCompleted ? 'check' : 'book'" class="size-4" />
                    {{ $isCompleted ? 'Tamamlandı' : 'Konu Anlatımı' }}
                </span>
            </div>
            <h1 id="lecture-title" class="lecture-title">{{ $lecture->title }}</h1>
            @if($lecture->summary)
                <p class="lecture-summary">{{ $lecture->summary }}</p>
            @endif
        </header>

        <div class="lecture-reading-grid">
            <div class="min-w-0">
                @if($readingContent['headings'])
                    <details class="site-card lecture-toc">
                        <summary>İçindekiler</summary>
                        <nav aria-label="Konu içindekiler">
                            @foreach($readingContent['headings'] as $heading)
                                <a href="#{{ $heading['anchor'] }}" data-level="{{ $heading['level'] }}">{{ $heading['title'] }}</a>
                            @endforeach
                        </nav>
                    </details>
                @endif
                <article class="site-card lecture-content" aria-labelledby="lecture-title">
                    {!! $readingContent['html'] !!}
                </article>
            </div>

            <aside class="lecture-side" aria-label="Çalışma ve konu gezinme">
                <section class="site-card lecture-side-card" aria-labelledby="lecture-progress-title">
                    <div class="lecture-side-icon {{ $isCompleted ? 'is-complete' : '' }}">
                        <x-student-icon :name="$isCompleted ? 'check' : 'book'" class="size-6" />
                    </div>
                    @auth
                    <h2 id="lecture-progress-title" class="lecture-side-title">{{ $isCompleted ? 'Konu tamamlandı' : 'Çalışma durumun' }}</h2>
                    <p class="lecture-side-copy">{{ $isCompleted ? 'Bu konuyu tamamlandı olarak işaretledin. İstersen yeniden çalışabilirsin.' : 'Okumayı bitirdiğinde konuyu tamamlandı olarak işaretleyebilirsin.' }}</p>
                    <form method="POST" action="{{ route('lectures.toggle-complete', $lecture) }}">
                        @csrf
                        <button type="submit" class="site-button-primary lecture-complete-button" aria-pressed="{{ $isCompleted ? 'true' : 'false' }}">
                            <x-action-label :icon="$isCompleted ? 'repeat' : 'check'">{{ $isCompleted ? 'Tamamlandı işaretini kaldır' : 'Konuyu tamamladım' }}</x-action-label>
                        </button>
                    </form>
                    @else
                        <h2 id="lecture-progress-title" class="lecture-side-title">Okuduğun konuları takip et</h2>
                        <p class="lecture-side-copy">Üye olduğunda bitirdiğin konuları işaretleyebilir, kalanları konu listende görebilirsin.</p>
                        <x-progress-signup class="lecture-complete-button" />
                    @endauth
                </section>

                <section class="site-card lecture-side-card" aria-labelledby="lecture-next-title">
                    <h2 id="lecture-next-title" class="lecture-side-title">{{ $nextLecture ? 'Sıradaki konu' : 'Çalışmaya devam et' }}</h2>
                    @if($nextLecture)
                        <a href="{{ route('lectures.show', [$category, $nextLecture]) }}" class="lecture-next">
                            <span>{{ $nextLecture->title }}</span>
                            <x-study-entry-icon />
                        </a>
                    @else
                        <p class="lecture-side-copy">Kategorideki son konuya geldin. Öğrendiklerini konu testleriyle pekiştirebilirsin.</p>
                        <a href="{{ route('topic-tests.categories') }}" class="lecture-next">
                            <span>Konu Testleri</span>
                            <x-study-entry-icon />
                        </a>
                    @endif
                </section>

                @if($previousLecture)
                    <section class="site-card lecture-side-card" aria-labelledby="lecture-previous-title">
                        <h2 id="lecture-previous-title" class="lecture-side-title">Önceki konu</h2>
                        <a href="{{ route('lectures.show', [$category, $previousLecture]) }}" class="lecture-next">
                            <x-student-icon name="arrow-left" class="size-5 shrink-0" />
                            <span>{{ $previousLecture->title }}</span>
                        </a>
                    </section>
                @endif

                <a href="{{ route('lectures.category', $category) }}" class="lecture-back">
                    <x-student-icon name="arrow-left" class="size-4 shrink-0" />
                    Kategorideki tüm konular
                </a>
            </aside>
        </div>
    </div>
</div>
@endsection
