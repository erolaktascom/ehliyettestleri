@extends('layouts.app')
@section('title', 'Premium Üyelik - ' . $siteName)
@section('content')
<div class="site-container py-8 sm:py-12">
    <a href="{{ route('dashboard') }}" class="text-sm text-muted hover:text-brand-700"><x-action-label icon="arrow-left">Genel Bakış</x-action-label></a>
    <header class="text-center max-w-2xl mx-auto mt-7"><span class="stitch-pill">Premium</span><h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-ink mt-4">Premium ile daha verimli hazırlan</h1><p class="text-base text-muted mt-4">{{ $siteName }} Premium sayesinde reklamsız çalış, yanlışlarını tekrar et ve gelişimini ayrıntılı olarak takip et.</p></header>
    @if(session('premium_notice'))<p role="status" class="max-w-2xl mx-auto mt-6 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 p-4 text-sm">{{ session('premium_notice') }}</p>@endif
    @if($user->isPremium())<p class="max-w-2xl mx-auto mt-6 text-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 p-4 text-sm font-semibold">Premium üyeliğin {{ $user->premium_until->format('d.m.Y H:i') }} tarihine kadar aktif.</p>@endif
    <div class="grid md:grid-cols-3 gap-5 mt-9">
        @foreach([
            ['01','Tamamen reklamsız','Mobilde ve masaüstünde sitedeki reklam alanları sana gösterilmez.'],
            ['02','Yanlışlardan tekrar testi','Tamamladığın testlerdeki son cevaplarına göre yanlışların toplanır. Doğru çözdüğün sorular havuzdan çıkar; yeniden yanlış yaptıkların tekrar eklenir.'],
            ['03','Detaylı gelişim raporu','Puan grafiğini, kategori bazında başarını ve son testlerindeki değişimi tarih ve test türüne göre incele.'],
        ] as [$number,$title,$text])<article class="site-card p-6"><span class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-700 font-extrabold">{{ $number }}</span><h2 class="text-lg font-extrabold text-ink mt-5">{{ $title }}</h2><p class="text-sm text-muted leading-relaxed mt-3">{{ $text }}</p></article>@endforeach
    </div>
    <div class="site-card max-w-2xl mx-auto mt-8 p-6 text-center">
        @if($user->isPremium())<a href="{{ route('student.detailed-performance') }}" class="site-button-primary">Gelişim raporumu aç</a>
        @else<p class="text-sm text-muted">Premium üyelik hakkında bilgi almak için bizimle iletişime geçebilirsin.</p><a href="{{ route('contact.show') }}" class="site-button-primary mt-4">İletişime geç</a>@endif
        <p class="text-xs text-muted mt-4">Normal üyelikte test çözme, temel başarı bilgileri ve tamamlanan testlerin sonuç ekranları kullanılmaya devam eder.</p>
    </div>
</div>
@endsection
