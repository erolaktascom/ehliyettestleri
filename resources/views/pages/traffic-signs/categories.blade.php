@extends('layouts.app')

@section('title', 'Trafik İşaretlerini Öğren - ' . $siteName)

<x-study-catalog-styles />
@section('content')
<section class="study-catalog">
    <div class="site-container">

<div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-ink">İşaretleri Öğren</h1>
                <p class="text-sm text-muted mt-1.5">Bir kategori seçerek trafik işaretlerini ve anlamlarını incele.</p>
            </div>
            <x-study-summary :total="$totalSignsCount" :completed="$categories->sum('known_count')" total-label="Toplam İşaret" completed-label="Öğrenilen" unit="İşaret" icon="sign" />
        </div>

        <div class="catalog-grid">
            @forelse($categories as $cat)
                <x-study-link-card :href="route('traffic-signs.learn', $cat)" :title="$cat->name" :icon="\App\Support\StudyCategoryIcon::resolve($cat->icon, $cat->name, 'sign')">
                    @auth
                        <div class="catalog-progress-meta"><span>{{ $cat->known_count }} / {{ $cat->signs_count }} işaret öğrenildi</span><strong>%{{ $cat->progress_percent }}</strong></div>
                        <div class="catalog-progress-track" aria-hidden="true"><span style="width: {{ max(0,min(100,$cat->progress_percent)) }}%"></span></div>
                    @else
                        <span>{{ $cat->signs_count }} işaret</span>
                    @endauth
                </x-study-link-card>
            @empty
                <p class="catalog-empty">Henüz işaret kategorisi bulunmuyor.</p>
            @endforelse
        </div>
        <div class="site-card mt-10 p-6 bg-brand-50/50 border-brand-100">
            <h3 class="font-bold text-sm text-brand-900">💡 Sınav Tavsiyesi</h3>
            <p class="text-xs sm:text-sm text-muted mt-1 leading-relaxed">Her gün bir kategorideki işaretleri gözden geçirerek görsel hafızanızı güçlendirebilirsiniz.</p>
        </div>
    </div>
</section>
@endsection
