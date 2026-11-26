@extends('layouts.app')
@section('title', 'Arama - ' . $siteName)
@section('robots', 'noindex, follow')
@section('content')
<section class="site-container py-8 sm:py-12">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl sm:text-3xl font-extrabold mb-6">Sitede Ara</h1>
        <div class="site-card p-5 sm:p-6"><x-site-search-form :query="$query" /></div>
        @if($results)
            <p class="text-sm text-muted my-6">“{{ $query }}” için {{ $results->total() }} sonuç bulundu.</p>
            <div class="space-y-3">
                @forelse($results as $result)
                    <a href="{{ $result->url }}" class="site-card block p-5 hover:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <p class="text-xs font-bold text-brand-700 mb-2">{{ $result->label }}</p>
                        <h2 class="text-base font-bold break-words">{{ $result->title }}</h2>
                        @if($result->category_name)<p class="text-xs text-muted mt-2">{{ $result->category_name }}</p>@endif
                    </a>
                @empty
                    <div class="site-card p-8 text-center">
                        <h2 class="font-bold">Aradığın içerik bulunamadı.</h2>
                        <p class="text-sm text-muted mt-2">Daha kısa veya farklı bir kelimeyle tekrar arayabilirsin.</p>
                        <a href="{{ route('home') }}" class="site-button-primary mt-5"><x-action-label icon="home">Ana Sayfaya Dön</x-action-label></a>
                    </div>
                @endforelse
            </div>
            @if($results->hasPages())
                <nav aria-label="Arama sonuçları sayfaları" class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
                    @if($results->previousPageUrl())<a class="site-button-secondary" href="{{ $results->previousPageUrl() }}" rel="prev"><x-action-label icon="arrow-left">Önceki</x-action-label></a>@endif
                    <span class="text-muted">Sayfa {{ $results->currentPage() }} / {{ $results->lastPage() }}</span>
                    @if($results->hasMorePages())<a class="site-button-secondary" href="{{ $results->nextPageUrl() }}" rel="next"><x-action-label icon="arrow">Sonraki</x-action-label></a>@endif
                </nav>
            @endif
        @else
            <p class="text-sm text-muted mt-6">Deneme sınavları, konu testleri, dersler, blog yazıları ve sayfaların başlıklarında arama yapabilirsin.</p>
        @endif
    </div>
</section>
@endsection
