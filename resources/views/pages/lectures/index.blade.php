@extends('layouts.app')

@section('title', 'Konu Anlatımı - ' . $siteName)

<x-study-catalog-styles />
@section('content')
<section class="study-catalog">
    <div class="site-container">

<!-- Header -->
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-end justify-between gap-6">
            <div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-ink">Konu Anlatımı</h1>
                <p class="text-base text-muted mt-2 max-w-2xl">Sınav müfredatına uygun kategoriler ve adım adım yapılandırılmış konu anlatımları ile eksiksiz hazırlanın.</p>
            </div>

            <x-study-summary :total="$totalLecturesCount" :completed="$totalCompletedCount" total-label="Toplam Ders" unit="Konu" icon="book" />
        </div>

        <div class="catalog-grid">
            @forelse($categories as $cat)
                <x-study-link-card :href="route('lectures.category', $cat)" :title="$cat->name" :icon="\App\Support\StudyCategoryIcon::resolve($cat->icon, $cat->name)">
                    @auth
                    <div class="catalog-progress-meta"><span>{{ $cat->completed_count }} / {{ $cat->total_count }} konu tamamlandı</span><strong>%{{ $cat->progress_percent }}</strong></div>
                    <div class="catalog-progress-track" aria-hidden="true"><span style="width: {{ max(0,min(100,$cat->progress_percent)) }}%"></span></div>
                    @else
                        <span>{{ $cat->total_count }} konu</span>
                    @endauth
                </x-study-link-card>
            @empty
                <p class="catalog-empty">Henüz yayında konu kategorisi bulunmuyor.</p>
            @endforelse
        </div>
        <div class="site-card mt-10 p-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-brand-50/60 border-brand-100">
            <div>
                <h3 class="font-bold text-sm text-brand-900">Müfredat Tavsiyesi</h3>
                <p class="text-xs sm:text-sm text-muted mt-1">Tüm konu başlıklarını tamamladıktan sonra 45 dakikalık MEB simülasyonunu çözmeniz tavsiye edilir.</p>
            </div>
            <a href="{{ route('exams.index') }}" class="site-button-primary py-2 px-5 text-xs shrink-0">
                <x-action-label icon="exam">Denemelere Git</x-action-label>
            </a>
        </div>
    </div>
</section>
@endsection
