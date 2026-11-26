@extends('layouts.app')

@section('title', 'Konu Testleri - ' . $siteName)

<x-study-catalog-styles />
@section('content')
<section class="study-catalog">
    <div class="site-container">

<!-- Header -->
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-ink">Ehliyet Konu Testleri</h1>
            <p class="text-base text-muted mt-2 max-w-2xl">Çalışmak istediğiniz kategoriyi seçin; o kategoriye ait testler bir sonraki ekranda listelenir.</p>
            </div>
            <x-study-summary :total="$totalStudyCount" :completed="$completedStudyCount" />
        </div>

        <div class="catalog-grid">
            @forelse($categories as $category)
                <x-study-link-card :href="route('topic-tests.category', $category)" :title="$category->name" :icon="$category->studyIcon()">
                    <span>{{ $category->topic_tests_count }} test</span>
                </x-study-link-card>
            @empty
                <p class="catalog-empty">Henüz yayında test kategorisi bulunmuyor.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
