@extends('layouts.app')
@section('seo-error', '1')
@section('title', 'Sayfa Bulunamadı - ' . $siteName)
@section('robots', 'noindex, follow')
@section('hide-ads', '1')
@section('content')
<section class="site-container py-8 sm:py-12" aria-labelledby="error-title">
    <div class="site-card overflow-hidden grid md:grid-cols-2 max-w-5xl mx-auto">
        <div class="p-6 sm:p-10 lg:p-12 flex flex-col justify-center">
            <p class="text-xs font-extrabold tracking-widest uppercase text-brand-700 mb-5">404 · Sayfa bulunamadı</p>
            <h1 id="error-title" class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">Bu yol bir yere<br>çıkmıyor.</h1>
            <p class="text-sm text-muted leading-relaxed mt-5 mb-6">Aradığın sayfa taşınmış, kaldırılmış veya adresi yanlış yazılmış olabilir. Arama yaparak istediğin içeriğe ulaşabilirsin.</p>
            <x-site-search-form />
            <a class="site-button-primary self-start mt-5" href="{{ route('home') }}"><x-action-label icon="home">Ana Sayfaya Dön</x-action-label></a>
        </div>
        <div class="bg-brand-50 flex items-center justify-center p-6 order-first md:order-last border-b md:border-b-0 md:border-l border-brand-100 [&>svg]:w-full [&>svg]:max-w-[220px] md:[&>svg]:max-w-[370px]" aria-hidden="true">
            <svg viewBox="0 0 380 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="190" cy="145" r="112" fill="#e0ebff"/>
                    <path d="M28 250h324" stroke="#c7d9ff" stroke-width="2" stroke-linecap="round"/>
                    <path d="M152 250l22-111h28l28 111" fill="#fff"/>
                    <path d="M190 153v12m0 15v16m0 17v23" stroke="#9ebeff" stroke-width="3" stroke-linecap="round"/>
                    <text x="190" y="170" text-anchor="middle" fill="#1e2d85" font-family="'Plus Jakarta Sans',system-ui,sans-serif" font-size="116" font-weight="800" letter-spacing="-7">404</text>
                    <path d="M288 250V199" stroke="#9ebeff" stroke-width="7"/>
                    <rect x="263" y="169" width="50" height="49" rx="9" fill="#2339d2" stroke="#fff" stroke-width="4"/>
                    <path d="M288 204v-21" stroke="#fff" stroke-width="6"/>
                    <path d="M277 183h22" stroke="#ffb4a8" stroke-width="7"/>
                    <path d="M78 250v-22m-8 8 8 8 8-12" stroke="#9ebeff" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="74" cy="78" r="5" fill="#c7d9ff"/>
                    <path d="M305 87h12m-6-6v12" stroke="#9ebeff" stroke-width="3" stroke-linecap="round"/>
                </svg>
        </div>
    </div>
</section>
@endsection
