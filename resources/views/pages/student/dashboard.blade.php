@extends('layouts.app')
@section('title', 'Öğrenci Portalı - Genel Bakış - ' . $siteName)

@push('styles')
<style>
    .student-overview { --ink:#14213d; --muted:#52617a; --blue:#2339d2; --navy:#1e2d85; --wash:#f0f4ff; --line:#dfe6f1; color:var(--ink); background:#f8faff; padding:24px 0 36px; }
    .student-overview .portal-icon { width:18px; height:18px; flex:none; }
    .student-overview :is(a,button):focus-visible { outline:3px solid #4871f4; outline-offset:3px; }
    .overview-top, .overview-body { display:grid; gap:16px; }
    .overview-top { grid-template-columns:minmax(0,2fr) minmax(0,1fr); margin-bottom:16px; }
    .overview-body { grid-template-columns:minmax(0,2fr) minmax(0,.92fr); align-items:start; }
    .overview-column { display:grid; min-width:0; gap:16px; }
    .ov-panel { min-width:0; border-radius:16px; padding:clamp(16px,2vw,24px); }
    .ov-panel h2, .ov-heading h2 { font-size:19px; font-weight:800; line-height:1.35; letter-spacing:-.45px; }
    .ov-heading, .ov-row, .ov-actions, .ov-count-head { display:flex; align-items:center; gap:10px; }
    .ov-heading, .ov-count-head { justify-content:space-between; }
    .ov-heading { margin-bottom:14px; flex-wrap:wrap; }
    .ov-link { color:var(--blue); font-weight:700; font-size:13px; }
    .ov-link:hover { text-decoration:underline; }
    .ov-hero { padding:clamp(20px,3vw,36px); border-radius:16px; color:white; background:radial-gradient(ellipse at 100% 110%,#3249af 0,transparent 50%),linear-gradient(112deg,#17245f,#1e2d85 75%); }
    .ov-hero h1 { max-width:27ch; font-size:clamp(25px,3vw,38px); line-height:1.16; font-weight:800; letter-spacing:-.9px; margin:12px 0 8px; overflow-wrap:anywhere; }
    .ov-hero p, .ov-premium p { font-size:14px; line-height:1.55; color:#e0ebff; }
    .ov-badge { display:inline-flex; align-items:center; gap:6px; border-radius:7px; padding:4px 9px; font-size:12px; font-weight:700; color:#fff; background:#ffffff24; }
    .ov-badge .portal-icon { width:14px; height:14px; }
    .ov-actions { flex-wrap:wrap; margin-top:18px; }
    .ov-hero .site-button-primary { background:#fff; color:#1e30aa; }
    .ov-hero .site-button-secondary { background:#ffffff12; border-color:#ffffff54; color:white; }
    .ov-hero .site-button-secondary:hover { background:#ffffff28; }
    .ov-count { display:flex; flex-direction:column; justify-content:space-between; gap:14px; }
    .ov-count-head { flex-wrap:wrap; }
    .ov-count-head h2 { display:flex; align-items:center; gap:7px; font-size:14px; }
    .ov-count-head .portal-icon, .ov-count-figure { color:#1e2d85; }
    .ov-count time, .ov-score { font-weight:700; font-size:12px; color:#2339d2; background:var(--wash); padding:4px 8px; border-radius:6px; }
    .ov-count-figure { font-size:17px; font-weight:700; line-height:1.3; }
    .ov-count-figure b { font-size:clamp(32px,3.6vw,48px); font-weight:800; line-height:1; letter-spacing:-1.5px; }
    .ov-count-foot { display:flex; align-items:center; justify-content:space-between; gap:8px; border-top:1px solid var(--line); padding-top:10px; }
    .ov-category-head { display:flex; align-items:center; gap:10px; }
    .ov-stat small, .ov-category small, .ov-caption { color:var(--muted); font-size:12px; line-height:1.45; }
    .ov-stats,.ov-categories { display:grid; gap:14px; }
    .ov-stats { grid-template-columns:repeat(4,minmax(0,1fr)); }
    .ov-categories { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .ov-stats .site-card, .ov-categories .site-card, .ov-history .site-card { border-radius:14px; border-color:#e5eaf3; box-shadow:0 3px 12px #17255406; }
    .student-overview a.site-card:hover { border-color:#bacafb; box-shadow:0 3px 10px #2339d20d; }
    .ov-stat,.ov-category { padding:14px; min-width:0; }
    .ov-stat { display:flex; flex-direction:column; }
    .ov-stat-head { display:flex; align-items:center; justify-content:space-between; font-weight:650; font-size:13px; gap:6px; flex:1; }
    .ov-stat > strong { display:block; font-size:30px; line-height:1.1; font-weight:800; color:var(--navy); margin:12px 0 4px; }
    .ov-stat.review > strong { color:#ae2e44; }
    .ov-icon { width:34px; height:34px; display:grid; place-items:center; flex:none; color:#263aae; background:#e9edff; border-radius:9px; }
    .ov-icon[data-icon="first-aid"],.review .ov-icon { background:#ffe8e7; color:#a82842; }
    .ov-icon[data-icon="courtesy"],.ov-icon[data-icon="bookmark"] { background:#def3ff; color:#135377; }
    .ov-icon[data-icon="play"] { background:var(--navy); color:white; }
    .ov-category { display:block; }
    .ov-category-head strong { display:block; font-size:15px; line-height:1.4; font-weight:700; overflow-wrap:anywhere; }
    .ov-category-head small { display:block; }
    .ov-progress-label { display:flex; align-items:center; justify-content:space-between; margin:12px 0 4px; font-size:11px; color:var(--muted); }
    .ov-progress-label b { color:var(--navy); }
    .ov-track { height:5px; border-radius:5px; background:#e7ecf7; overflow:hidden; }
    .ov-track span { display:block; height:100%; border-radius:inherit; background:#516bf0; }
    @media(min-width:901px) { .ov-category:last-child:nth-child(odd) { grid-column:span 2; } }
    .ov-history { display:grid; gap:8px; }
    .ov-history .ov-row { padding:10px 14px; }
    .ov-row { min-height:46px; padding:7px 0; }
    .ov-row + .ov-row { border-top:1px solid #edf0f6; }
    .ov-row-copy { flex:1; min-width:0; overflow-wrap:anywhere; }
    .ov-row-copy strong, .ov-row-copy small { display:block; }
    .ov-row-copy strong { font-size:14px; font-weight:700; }
    .ov-row-copy small { font-size:12px; color:var(--muted); }
    .ov-row > .portal-icon { width:15px; color:var(--blue); }
    .ov-mode { padding:10px; background:#f5f7fb; border-radius:10px; }
    .ov-mode + .ov-mode { margin-top:7px; border:0; }
    .ov-mode .ov-icon { background:#1e2d85; color:white; }
    .ov-mode + .ov-mode .ov-icon { background:#2d4ee7; }
    .ov-panel a.ov-mode:hover { background:#e9efff; }
    a.ov-row:hover .ov-row-copy strong { color:var(--blue); }
    .ov-score { white-space:nowrap; }
    .ov-premium { color:white; background:linear-gradient(120deg,#193c71,#1e2d85); }
    .ov-premium h2 { margin:10px 0 5px; }
    .ov-premium .ov-actions { display:grid; gap:8px; }
    .ov-premium .site-button-primary { background:#fff; color:#1e30aa; width:100%; }
    .ov-premium .site-button-secondary { background:#ffffff12; color:white; border-color:#ffffff54; }
    @media(max-width:1080px) { .ov-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:760px) {
        .overview-top, .overview-body { grid-template-columns:1fr; }
        .overview-column, .overview-top, .overview-body { gap:12px; }
        .student-overview { padding:14px 0 24px; }
        .overview-top { margin-bottom:12px; }
        .ov-count { gap:10px; }
        .ov-row { min-height:44px; }
    }
    @media(max-width:480px) {
        .ov-stats,.ov-categories { gap:8px; }
        .ov-categories { grid-template-columns:1fr; }
        .ov-stat { padding:11px; }
        .ov-stat-head .ov-icon { width:26px; height:26px; }
    }
</style>
@endpush

@section('content')
@php
    $examDays = $user->days_to_exam;
@endphp
<section class="student-overview">
    <div class="site-container">
        <header class="overview-top">
            <div class="ov-hero">
                <span class="ov-badge"><x-student-icon name="flag" />{{ $user->target_license_class ?: 'Öğrenci Portalı' }}</span>
                <h1>Merhaba, {{ $user->name }}.</h1>
                <p>{{ $completedExamsCount && $answeredUniqueCount ? $weakSummary : 'Ehliyet sınavına hazırlanmak için deneme çözebilir veya bir konu seçebilirsin.' }}</p>
                <div class="ov-actions">
                    @if($quickExam)
                        <form method="POST" action="{{ route('exams.start', $quickExam) }}">
                            @csrf
                            <button class="site-button-primary" type="submit"><x-action-label icon="play">Hemen deneme çöz</x-action-label></button>
                        </form>
                    @else
                        <a class="site-button-primary" href="{{ route('exams.index') }}"><x-action-label icon="exam">Denemeleri gör</x-action-label></a>
                    @endif
                    <a class="site-button-secondary" href="{{ route('lectures.index') }}"><x-action-label icon="book">Konu çalış</x-action-label></a>
                </div>
            </div>
            <section class="site-card ov-panel ov-count">
                <div class="ov-count-head">
                    <h2><x-student-icon name="calendar" /> Sınav takvimi</h2>
                    @if($user->driving_exam_date)
                        <time datetime="{{ $user->driving_exam_date->toDateString() }}">{{ $user->driving_exam_date->translatedFormat('d F Y') }}</time>
                    @endif
                </div>
                <div class="ov-count-figure">
                    @if($examDays === null)
                        Sınav tarihi bekleniyor
                    @elseif($examDays > 0)
                        <span aria-label="{{ $examDays }} gün kaldı"><b>{{ $examDays }}</b> gün kaldı</span>
                    @else
                        {{ $examDays === 0 ? 'Bugün sınav günü' : 'Sınav tarihi geçti' }}
                    @endif
                </div>
                <div class="ov-count-foot">
                    <span class="ov-caption">e-Sınav</span>
                    <a class="ov-link" href="{{ route('student.settings.profile') }}">{{ $examDays === null ? 'Sınav tarihini ekle' : 'Tarihi düzenle' }}</a>
                </div>
            </section>
        </header>

        <div class="overview-body">
            <div class="overview-column">
                <section>
                    <div class="ov-heading"><h2>Çalışma özetin</h2></div>
                    <div class="ov-stats">
                        <div class="site-card ov-stat">
                            <div class="ov-stat-head">Tamamlanan çalışma <span class="ov-icon"><x-student-icon name="check" /></span></div>
                            <strong>{{ $completedExamsCount }}</strong><small>Deneme ve konu testleri</small>
                        </div>
                        <div class="site-card ov-stat">
                            <div class="ov-stat-head">Son 3 çalışma ortalaması <span class="ov-icon"><x-student-icon name="chart" /></span></div>
                            <strong>{{ $completedExamsCount ? number_format($last3Avg, 1, ',', '.') : '—' }}</strong><small>{{ $completedExamsCount ? '100 puan üzerinden' : 'İlk sonucunla oluşacak' }}</small>
                        </div>
                        <div class="site-card ov-stat review">
                            <div class="ov-stat-head">Tekrar bekleyen soru <span class="ov-icon"><x-student-icon name="repeat" /></span></div>
                            <strong>{{ $unresolvedWrongCount }}</strong><small>{{ $answeredUniqueCount }} farklı soru yanıtlandı</small>
                        </div>
                        <a href="{{ route('student.saved-questions') }}" class="site-card ov-stat">
                            <div class="ov-stat-head">Kaydedilen Sorular <span class="ov-icon" data-icon="bookmark"><x-student-icon name="bookmark" /></span></div>
                            <strong>{{ $savedQuestionsCount }}</strong><small>Sorularımı aç →</small>
                        </a>
                    </div>
                </section>

                <section>
                    <div class="ov-heading">
                        <h2>Konu kategorileri</h2>
                        <a class="ov-link" href="{{ route('topic-tests.categories') }}">Tüm testler →</a>
                    </div>
                    <div class="ov-categories">
                        @forelse($categoryStats as $stat)
                            @php
                                $category = $stat['category'];
                                $icon = $category->studyIcon();
                                $categoryUrl = $category->topicTests()->where('is_published', true)
                                    ->orderByRaw("CASE WHEN section = 'topic_tests' THEN 0 ELSE 1 END")
                                    ->first()?->listUrl() ?? route('topic-tests.categories');
                            @endphp
                            <a class="site-card ov-category" href="{{ $categoryUrl }}">
                                <div class="ov-category-head">
                                    <span class="ov-icon" data-icon="{{ $icon }}"><x-student-icon :name="$icon" /></span>
                                    <div><strong>{{ $category->name }}</strong><small>{{ $stat['total_questions'] }} soru · {{ $stat['correct_questions'] }} doğru</small></div>
                                </div>
                                <div class="ov-progress-label"><span>İlerleme</span><b>%{{ $stat['percent'] }}</b></div>
                                <div class="ov-track" aria-hidden="true"><span style="width:{{ max(0, min(100, $stat['percent'])) }}%"></span></div>
                            </a>
                        @empty
                            <p class="ov-caption">Konu kategorileri eklendiğinde burada görebilirsin.</p>
                        @endforelse
                    </div>
                </section>

                <section>
                    <div class="ov-heading"><h2>Son çalışmaların</h2></div>
                    <div class="ov-history">
                        @forelse($recentAttempts as $attempt)
                            <a class="ov-row site-card" href="{{ route('exams.result', $attempt) }}">
                                <span class="ov-icon"><x-student-icon :name="$attempt->topic_test_id ? 'book' : 'exam'" /></span>
                                <span class="ov-row-copy">
                                    <strong>{{ $attempt->topicTest?->title ?? $attempt->exam?->title ?? 'Silinen test' }}</strong>
                                    <small>{{ $attempt->topic_test_id ? 'Konu testi' : 'Deneme sınavı' }} · {{ ($attempt->completed_at ?? $attempt->started_at)?->format('d.m.Y') }}</small>
                                </span>
                                <span class="ov-score">{{ number_format($attempt->score, 0, ',', '.') }} puan</span>
                            </a>
                        @empty
                            <p class="ov-caption">Henüz tamamlanmış bir çalışman yok. <a class="ov-link" href="{{ route('exams.index') }}">Deneme sınavlarını keşfet</a></p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="overview-column" aria-label="Çalışma araçları">
                <section class="site-card ov-panel">
                    <div class="ov-heading"><h2>Çalışma modları</h2></div>
                    <a class="ov-row ov-mode" href="{{ route('exams.index') }}">
                        <span class="ov-icon"><x-student-icon name="exam" /></span>
                        <span class="ov-row-copy"><strong>Deneme sınavları</strong><small>Süreli sınav provası</small></span>
                        <x-student-icon name="arrow" />
                    </a>
                    <a class="ov-row ov-mode" href="{{ route('topic-tests.categories') }}">
                        <span class="ov-icon"><x-student-icon name="book" /></span>
                        <span class="ov-row-copy"><strong>Konu Testleri</strong><small>Kategoriye göre soru çöz</small></span>
                        <x-student-icon name="arrow" />
                    </a>
                </section>

                <section class="site-card ov-panel">
                    <div class="ov-heading"><h2>Öğrenme alanları</h2></div>
                    <div class="ov-row">
                        <span class="ov-icon"><x-student-icon name="chart" /></span>
                        <span class="ov-row-copy"><strong>Hazırlık oranı</strong><small>{{ $uniqueCorrectCount }} / {{ $totalQuestions }} farklı soruda doğru cevap</small></span>
                        <span class="ov-score">%{{ $prepPercent }}</span>
                    </div>
                    <a class="ov-row" href="{{ route('lectures.index') }}">
                        <span class="ov-icon"><x-student-icon name="book" /></span>
                        <span class="ov-row-copy"><strong>Konu Anlatımı</strong><small>{{ $completedLecturesCount }} / {{ $totalLecturesCount }} konu tamamlandı</small></span>
                        <span class="ov-score">%{{ $lecturesPercent }}</span>
                    </a>
                    <a class="ov-row" href="{{ route('traffic-signs.index') }}">
                        <span class="ov-icon"><x-student-icon name="sign" /></span>
                        <span class="ov-row-copy"><strong>Trafik İşaretleri</strong><small>{{ $knownSignsCount }} / {{ $totalSignsCount }} işaret öğrenildi</small></span>
                        <span class="ov-score">%{{ $signsPercent }}</span>
                    </a>
                </section>

                <section class="ov-panel ov-premium">
                    <span class="ov-badge"><x-student-icon name="repeat" /> Premium</span>
                    <h2>Tekrar et, gelişimini gör</h2>
                    <p>Yanlışlarını yeniden çöz ve sonuçlarını performans raporunda incele.</p>
                    <div class="ov-actions">
                        @if($user->isPremium())
                            @if($unresolvedWrongCount > 0)
                                <form method="POST" action="{{ route('student.wrong-questions.start') }}">
                                    @csrf
                                    <button class="site-button-primary" type="submit">{{ $unresolvedWrongCount }} yanlış soruyu tekrar çöz</button>
                                </form>
                            @endif
                            <a class="site-button-secondary" href="{{ route('student.detailed-performance') }}">Performans raporunu aç</a>
                        @else
                            <a class="site-button-primary" href="{{ route('student.premium') }}">Premium avantajlarını keşfet</a>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>
</section>
@endsection
