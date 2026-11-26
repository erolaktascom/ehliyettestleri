@extends('layouts.app')
@section('title', 'Ehliyet E-Sınav Hazırlık Portalı - ' . $siteName)
<x-study-catalog-styles />
@push('styles')
<style>
    .home-refresh { padding: 28px 0 56px; }
    .home-refresh .home-hero {
        display:grid; grid-template-columns:minmax(0,1.12fr) minmax(0,1fr);
        overflow:hidden; background:#fff; border:1px solid #dfe6f1; border-radius:24px;
    }
    .home-hero-copy { align-self:center; padding:clamp(24px,3.2vw,44px); }
    .home-hero-badge { display:inline-flex; align-items:center; gap:8px; padding:7px 10px; border:1px solid #e1e7ff; border-radius:7px; background:#f0f3ff; color:#4558b5; font-size:10px; font-weight:700; }
    .home-hero-badge svg { width:14px; height:14px; }
    .home-hero-facts {
        display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); max-width:26rem; margin:22px 0;
        padding:12px 0; border:1px solid #dfe6f1; border-radius:14px; background:#f7f9ff;
    }
    .home-hero-facts > div { padding:0 clamp(8px,1.3vw,16px); }
    .home-hero-facts > div + div { border-left:1px solid #dde4f2; }
    .home-hero-facts dt { color:#52637b; font-size:12px; font-weight:600; }
    .home-hero-facts dd { display:flex; align-items:center; gap:6px; margin:4px 0 0; color:#172554; font-size:22px; font-weight:800; line-height:1.2; font-variant-numeric:tabular-nums; }
    .home-hero-facts svg { flex:none; width:16px; height:16px; color:#4558b5; }
    .home-hero-facts small { font-size:12px; font-weight:600; }
    .home-hero h1 { margin:12px 0; font-size:clamp(36px,4vw,52px); font-weight:800; letter-spacing:-.045em; line-height:1.08; color:#14213d; }
    .home-hero h1 span { display:block; color:#2b3cc8; }
    .home-hero-intro { max-width:42ch; font-size:15px; line-height:1.65; color:#52637b; }
    .home-hero-actions { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:20px; }
    .home-hero-actions :is(.site-button-primary,.site-button-secondary) { padding:12px 16px; font-size:13px; }
    .home-hero-visual { position:relative; clip-path:polygon(17% 0,100% 0,100% 100%,0 100%); background:#e8edf5; }
    .home-hero-visual img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:55% 52%; }
    .home-scenario-badge {
        position:absolute; top:clamp(10px,1.4vw,18px); right:clamp(10px,1.4vw,18px);
        display:inline-flex; align-items:center; gap:8px; padding:5px 11px; border-radius:999px;
        background:#14213ded; color:#fff; font-size:12px; font-weight:700; line-height:1.4;
    }
    @media (prefers-reduced-motion:no-preference) {
        .home-scenario-badge .green-light { animation:home-signal-pulse 2s ease-in-out infinite; }
    }
    @keyframes home-signal-pulse {
        50% { opacity:.45; }
    }
    .home-refresh .home-section-title { display:flex; justify-content:space-between; align-items:end; gap:16px; margin-top:40px; }
    .home-refresh .home-section-title h2 { color:#14213d; font-size:25px; letter-spacing:-.7px; font-weight:800; }
    .home-refresh .home-section-title p { font-size:13px; line-height:1.7; color:#64748b; margin-top:6px; }
    .home-refresh .catalog-grid { margin-top:20px; }
    .home-refresh .catalog-tile-main { min-height:106px; padding:20px 24px; }
    .home-refresh .catalog-progress-footer { font-size:12px; line-height:1.7; }
    .home-refresh a:focus-visible, .home-refresh button:focus-visible { outline:3px solid #516bf0; outline-offset:4px; }
    @media(max-width:700px) {
        .home-refresh .home-hero { grid-template-columns:minmax(0,1fr); border-radius:18px; }
        .home-hero-visual { grid-row:1; height:clamp(104px,28vw,160px); clip-path:polygon(0 0,100% 0,100% 78%,0 100%); }
        .home-hero-copy { padding:12px clamp(16px,4vw,26px) 18px; }
        .home-hero h1 { font-size:clamp(32px,7vw,40px); }
        .home-hero h1 span { display:inline; }
        .home-hero-intro { font-size:14px; }
        .home-hero-actions { margin-top:16px; }
        .home-hero-actions :is(.site-button-primary,.site-button-secondary) { padding:10px 12px; }
    }
    @media(max-width:479px) {
        .home-refresh { padding:16px 0 36px; }
        .home-refresh .home-section-title { margin-top:28px; }
        .home-refresh .home-section-title h2 { font-size:23px; }
        .home-refresh .catalog-tile-main { padding:18px 16px; }
    }
</style>
@endpush
@section('content')
<div class="home-refresh study-catalog">
    <div class="site-container">
        <section class="home-hero" aria-labelledby="home-title">
            <div class="home-hero-copy">
                <span class="home-hero-badge"><x-student-icon name="check" /> 2026 MEB Müfredatına Tam Uyumlu</span>
                <h1 id="home-title">Ehliyet sınavına <span>hazır mısın?</span></h1>
                <p class="home-hero-intro">Süreli deneme çöz veya kavşak senaryolarıyla trafik bilgini sına.</p>
                @if($featuredExam)
                    <dl class="home-hero-facts" aria-label="Öne çıkan deneme bilgileri">
                        <div>
                            <dt>Soru sayısı</dt>
                            <dd><x-student-icon name="exam" />{{ $featuredExam->published_questions_count }}</dd>
                        </div>
                        <div>
                            <dt>Süre</dt>
                            <dd><x-student-icon name="clock" /><span>{{ $featuredExam->duration_minutes }} <small>dk</small></span></dd>
                        </div>
                        <div>
                            <dt>Geçme puanı</dt>
                            <dd><x-student-icon name="flag" />{{ $featuredExam->passing_score }}</dd>
                        </div>
                    </dl>
                @endif
                <div class="home-hero-actions">
                    @if($featuredExam)
                        <form method="POST" action="{{ route('exams.start', $featuredExam) }}">
                            @csrf
                            <button type="submit" class="site-button-primary"><x-action-label icon="play">Hızlı Deneme Başlat</x-action-label></button>
                        </form>
                    @endif
                    <a href="{{ route('exams.index') }}" class="site-button-secondary"><x-action-label icon="exam">Tüm denemeler</x-action-label></a>
                </div>
            </div>
            <div class="home-hero-visual">
                <img src="{{ asset('images/animated-questions-cover.webp') }}" alt="Trafik ışıklı bir kavşakta bekleyen araçlar" width="1448" height="1086" fetchpriority="high">
                <span class="home-scenario-badge">
                    <svg width="12" height="22" viewBox="0 0 12 22" aria-hidden="true" focusable="false">
                        <rect x=".5" y=".5" width="11" height="21" rx="4" fill="#101c32" stroke="#8191a8"/>
                        <circle cx="6" cy="5" r="2.2" fill="#eb9691"/>
                        <circle cx="6" cy="11" r="2.2" fill="#e7bd70"/>
                        <circle class="green-light" cx="6" cy="17" r="2.2" fill="#6ee7b7"/>
                    </svg>
                    Trafik senaryosu
                </span>
            </div>
        </section>
        <section aria-labelledby="home-study-title">
            <div class="home-section-title"><div><h2 id="home-study-title">Nasıl çalışmak istersin?</h2><p>Konunu seç, öğren ve kendini test et.</p></div></div>
            <div class="catalog-grid">
                <x-study-link-card :href="route('lectures.index')" title="Konu Anlatımı" icon="book">Temel bilgileri kısa ve anlaşılır konu anlatımlarıyla öğren.</x-study-link-card>
                <x-study-link-card :href="route('topic-tests.categories')" title="Konu Testleri" icon="exam">Çalışmak istediğin kategoriyi seç, öğrendiklerini pekiştir.</x-study-link-card>
                <x-study-link-card :href="route('traffic-signs.index')" title="Trafik İşaretleri" icon="sign">İşaretleri kartlarla öğren, tekrar etmek istediklerini ayır.</x-study-link-card>
                <x-study-link-card :href="route('animated-questions.index')" title="Animasyonlu Sorular" icon="play">Hareketli trafik senaryolarını değerlendir, soruları çöz.</x-study-link-card>
            </div>
        </section>
    </div>
</div>
@endsection
