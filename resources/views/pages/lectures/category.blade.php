@extends('layouts.app')

@section('title', $category->name . ' - ' . $siteName)

<x-study-catalog-styles />
@section('content')
<section class="study-catalog">
    <div class="site-container">
        <nav class="flex items-center gap-2 text-xs text-muted mb-4 sm:mb-5">
            <a href="{{ route('home') }}" class="hover:text-brand-700">Ana Sayfa</a>
            <span>›</span>
            <a href="{{ route('lectures.index') }}" class="hover:text-brand-700">Konu Anlatımı</a>
            <span>›</span>
            <span class="font-bold text-ink">{{ $category->name }}</span>
        </nav>

        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-ink">{{ $category->name }}</h1>
                    <p class="text-base text-muted mt-2">{{ $category->description }}</p>
                </div>
                @auth
                <div class="flex items-center gap-2 text-xs font-bold shrink-0 bg-brand-50 p-3 rounded-xl border border-brand-100">
                    <span class="text-slate-600">○ Başlanmadı</span>
                    <span class="text-emerald-700">✓ Tamamlandı</span>
                </div>
                @else
                    <x-study-summary :total="$totalCount" :completed="0" total-label="Toplam Konu" unit="Konu" icon="book" />
                @endauth
            </div>

            @auth
            <!-- Progress Meter -->
            <div class="mt-6 pt-6 border-t border-slate-100 flex items-center gap-5">
                <div class="grid size-14 shrink-0 place-items-center rounded-full border-4 border-brand-700 text-base font-extrabold text-brand-700">
                    %{{ $progressPercent }}
                </div>
                <div class="flex-1">
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <strong class="text-slate-700">Kategori Tamamlanma Oranı</strong>
                        <span class="text-brand-700">{{ $completedCount }} / {{ $totalCount }} konu tamamlandı</span>
                    </div>
                    <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-brand-700 transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>
            </div>
            @endauth
        </div>

        <div class="catalog-grid">
            @forelse($lectures as $lecture)
                <x-study-link-card :href="route('lectures.show', [$category, $lecture])" :title="$lecture->title" :number="$loop->iteration" :status="auth()->check() ? (in_array($lecture->id, $completedIds) ? 'Tamamlandı' : 'Başlanmadı') : null" />
            @empty
                <p class="catalog-empty">Bu kategoride henüz ders bulunmuyor.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection