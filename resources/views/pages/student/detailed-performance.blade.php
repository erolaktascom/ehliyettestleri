@extends('layouts.app')
@section('title', 'Detaylı Performans Raporu - ' . $siteName)
@section('content')
<div class="site-container py-6 sm:py-9 space-y-6">
    <header class="flex flex-wrap justify-between gap-4 items-start"><div><span class="stitch-pill">Premium</span><h1 class="mt-3 text-2xl sm:text-3xl font-extrabold text-ink">Detaylı Performans</h1><p class="text-sm text-muted mt-2">Tamamladığın sınav ve testlerdeki gelişimini takip et.</p></div><a href="{{ route('dashboard') }}" class="site-button-secondary"><x-action-label icon="arrow-left">Genel Bakış</x-action-label></a></header>
    <form method="GET" class="site-card p-5 flex flex-wrap items-end gap-4">
        <label class="text-sm font-bold text-ink">Dönem<select name="range" class="block mt-2 rounded-xl border border-slate-300 p-2.5 text-sm font-normal"><option value="all" @selected($range==='all')>Tüm zamanlar</option><option value="30" @selected($range==='30')>Son 30 gün</option><option value="90" @selected($range==='90')>Son 90 gün</option></select></label>
        <label class="text-sm font-bold text-ink">Test türü<select name="kind" class="block mt-2 rounded-xl border border-slate-300 p-2.5 text-sm font-normal"><option value="all" @selected($kind==='all')>Tüm sınav ve testler</option><option value="exams" @selected($kind==='exams')>Deneme sınavları</option><option value="topic_tests" @selected($kind==='topic_tests')>Konu ve animasyon testleri</option></select></label>
        <button class="site-button-primary" type="submit">Uygula</button><p class="text-xs text-muted w-full">Yalnız tamamlanmış oturumlar hesaplanır. Yanlışlardan tekrar testleri rapora dahil edilmez.</p>
    </form>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach(['Tamamlanan test'=>$totalAttempts,'Ortalama puan'=>$totalAttempts ? number_format($averageScore,1,',','.') : '—','Son 5 test ortalaması'=>$recentAverage!==null ? number_format($recentAverage,1,',','.') : '—','Başarılı oturum'=>$passedAttempts] as $label=>$value)
        <div class="site-card p-5"><p class="text-xs text-muted font-semibold">{{ $label }}</p><strong class="block mt-2 text-2xl font-extrabold text-brand-900">{{ $value }}</strong></div>
        @endforeach
    </div>
    <section class="site-card p-5 sm:p-7">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-extrabold text-ink">Puan gelişimi</h2><p class="text-xs text-muted mt-1">Seçili dönemdeki son 20 tamamlanmış test · 100 üzerinden puan</p></div>
        @if($trend!==null)<span class="rounded-xl px-3 py-2 text-sm font-bold {{ $trend>=0 ? 'bg-emerald-50 text-emerald-700':'bg-amber-50 text-amber-800' }}">{{ $trend>0?'+':'' }}{{ number_format($trend,1,',','.') }} puan<small class="block font-normal text-xs">Son 5 test / önceki 5 test</small></span>@endif</div>
        @if($chart->isNotEmpty())
        @php $points=$chart->map(fn($a,$i)=>(50+($chart->count()>1 ? $i*700/($chart->count()-1):350)).','. (220-max(0,min(100,$a->score))*1.8))->implode(' '); @endphp
        <svg viewBox="0 0 800 255" class="w-full mt-5" role="img" aria-labelledby="score-chart-title"><title id="score-chart-title">Tamamlanmış testlerin puan grafiği; puanlar aşağıdaki sonuç listesinde de yer alır.</title>
            @foreach([0,50,100] as $tick)<line x1="50" y1="{{ 220-$tick*1.8 }}" x2="750" y2="{{ 220-$tick*1.8 }}" stroke="#e2e8f0"/><text x="35" y="{{ 225-$tick*1.8 }}" text-anchor="end" font-size="12" fill="#64748b">{{ $tick }}</text>@endforeach
            <polyline points="{{ $points }}" fill="none" stroke="#273cc9" stroke-width="3"/>
            @foreach($chart as $i=>$attempt)<circle cx="{{ 50+($chart->count()>1 ? $i*700/($chart->count()-1):350) }}" cy="{{ 220-max(0,min(100,$attempt->score))*1.8 }}" r="5" fill="#273cc9"><title>{{ ($attempt->completed_at ?? $attempt->started_at)?->format('d.m.Y H:i') }} · {{ $attempt->score }} puan</title></circle>@endforeach
            <text x="50" y="248" font-size="12" fill="#64748b">{{ ($chart->first()->completed_at ?? $chart->first()->started_at)?->format('d.m.Y') }}</text><text x="750" y="248" text-anchor="end" font-size="12" fill="#64748b">{{ ($chart->last()->completed_at ?? $chart->last()->started_at)?->format('d.m.Y') }}</text>
        </svg>
        @if($trend===null)<p class="text-xs text-muted mt-3">İki dönemi karşılaştırmak için seçili filtrelerde en az 10 tamamlanmış test gerekli.</p>@endif
        @else<p class="py-10 text-sm text-muted">Bu filtrelerde tamamlanmış test bulunmuyor. Test çözdükçe puan grafiğin burada oluşacak.</p>@endif
    </section>
    <div class="grid lg:grid-cols-3 gap-6">
        <section class="site-card p-5 sm:p-7 lg:col-span-2"><h2 class="text-lg font-extrabold text-ink">Kategori bazında başarı</h2><p class="text-xs text-muted mt-1">Yanıtlanan sorular üzerinden hesaplanır; boşlar hariçtir. Tekrar çözülen sorular her oturumda sayılır.</p><div class="mt-5 space-y-5">
            @forelse($categoryPerformance as $category)<div><div class="flex justify-between gap-3 text-sm"><span class="font-semibold text-ink">{{ $category->name }}</span><strong class="text-brand-700">%{{ $category->percent }}</strong></div><div class="mt-2 bg-slate-100 h-2 rounded-full overflow-hidden"><div class="h-full bg-brand-600 rounded-full" style="width:{{ $category->percent }}%"></div></div><p class="text-xs text-muted mt-2">{{ $category->correct }} doğru · {{ $category->answered-$category->correct }} yanlış · {{ $category->answered }} yanıt</p></div>
            @empty<p class="text-sm text-muted">Kategori analizi için henüz yanıt verisi yok.</p>@endforelse
        </div></section>
        <section class="site-card p-5 sm:p-7"><h2 class="text-lg font-extrabold text-ink">Cevap özeti</h2><dl class="mt-5 space-y-4 text-sm"><div class="flex justify-between"><dt>Doğru</dt><dd class="font-bold text-emerald-700">{{ $correctTotal }}</dd></div><div class="flex justify-between"><dt>Yanlış</dt><dd class="font-bold text-red-600">{{ $incorrectTotal }}</dd></div><div class="flex justify-between"><dt>Boş</dt><dd class="font-bold text-slate-600">{{ $emptyTotal }}</dd></div></dl><p class="text-xs text-muted mt-5">Seçili dönemde tamamlanan oturumların kayıtlı sonuçlarıdır.</p></section>
    </div>
    <section class="site-card p-5 sm:p-7"><h2 class="text-lg font-extrabold text-ink">Sınav ve test geçmişi</h2><div class="divide-y divide-slate-100 mt-4">
        @forelse($attempts as $attempt)<article class="py-4 flex flex-wrap items-center justify-between gap-4"><div class="min-w-0"><h3 class="font-bold text-sm text-ink break-words">{{ $attempt->topicTest?->title ?? $attempt->exam?->title ?? 'Silinen test' }}</h3><p class="text-xs text-muted mt-1">{{ ($attempt->completed_at ?? $attempt->started_at)?->format('d.m.Y H:i') }} · {{ $attempt->score }} puan</p><p class="text-xs text-muted mt-1">{{ $attempt->correct_count }} doğru · {{ $attempt->incorrect_count }} yanlış · {{ $attempt->empty_count }} boş</p></div><a href="{{ route('exams.result',$attempt) }}" class="site-button-secondary text-xs">Sonucu görüntüle</a></article>
        @empty<p class="py-5 text-sm text-muted">Henüz tamamlanmış test bulunmuyor.</p>@endforelse
    </div><div class="mt-4">{{ $attempts->links() }}</div></section>
</div>
@endsection
