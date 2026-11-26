@extends('layouts.app')

@section('title', ($page->seo_title ?: $page->title) . ' - ' . $siteName)

@push('styles')
<style>
    .managed-page { padding: 20px 0 56px; }
    .managed-page-card { width: 100%; min-width: 0; overflow: hidden; }
    .managed-page-heading { padding: 32px; border-bottom: 1px solid #e8edf5; background: #fff; }
    .managed-page-title { margin: 0; color: #0f172a; font-size: clamp(1.75rem, 4vw, 2.5rem); line-height: 1.2; font-weight: 800; letter-spacing: -.035em; overflow-wrap: anywhere; }
    .managed-page-description { margin: 14px 0 0; max-width: 70ch; font-size: 1rem; line-height: 1.75; color: #64748b; overflow-wrap: anywhere; }
    .managed-page-date { display: inline-flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-top: 20px; font-size: .75rem; color: #64748b; }
    .managed-page-date svg { width: 16px; height: 16px; flex-shrink: 0; }
    .managed-page-content { padding: 32px; color: #475569; font-size: 1rem; line-height: 1.9; overflow-wrap: anywhere; }
    .managed-page-content > :first-child { margin-top: 0; }
    .managed-page-content > :last-child { margin-bottom: 0; }
    .managed-page-content :is(h1,h2,h3,h4,h5,h6) { color: #0f172a; font-weight: 800; line-height: 1.4; margin: 2rem 0 .8rem; letter-spacing: -.02em; }
    .managed-page-content :is(h1,h2) { font-size: 1.5rem; }
    .managed-page-content h3 { font-size: 1.25rem; }
    .managed-page-content :is(h4,h5,h6) { font-size: 1.1rem; }
    .managed-page-content p { margin: 0 0 1.15rem; }
    .managed-page-content :is(ul,ol) { padding-left: 1.5rem; margin: 1rem 0 1.5rem; }
    .managed-page-content ul { list-style: disc; }
    .managed-page-content ol { list-style: decimal; }
    .managed-page-content li { margin: .4rem 0; padding-left: .2rem; }
    .managed-page-content a { color: #263fe5; text-decoration: underline; text-underline-offset: 3px; }
    .managed-page-content a:focus-visible { outline: 2px solid #92a6fb; outline-offset: 3px; }
    .managed-page-content :is(strong,b) { color: #1e293b; font-weight: 700; }
    .managed-page-content blockquote { margin: 1.5rem 0; padding: 1rem 1.25rem; border-left: 3px solid #516bf0; border-radius: 0 12px 12px 0; background: #f0f3ff; }
    .managed-page-content blockquote p:last-child { margin-bottom: 0; }
    .managed-page-content :is(img,video) { max-width: 100%; height: auto; border-radius: 12px; margin: 1.5rem auto; }
    .managed-page-content iframe { width: 100%; max-width: 100%; aspect-ratio: 16 / 9; height: auto; border: 0; border-radius: 12px; }
    .managed-page-content table { display: block; width: 100%; max-width: 100%; overflow-x: auto; border-collapse: collapse; margin: 1.5rem 0; }
    .managed-page-content :is(th,td) { border: 1px solid #e2e8f0; padding: .65rem .85rem; text-align: left; }
    .managed-page-content th { background: #f8fafc; color: #1e293b; }
    .managed-page-content pre { overflow-x: auto; background: #f8fafc; padding: 1rem; border-radius: 12px; margin: 1rem 0; }
    .managed-page-content hr { border-color: #e2e8f0; margin: 2rem 0; }
    @media (max-width: 639px) {
        .managed-page { padding: 16px 0 32px; }
        .managed-page-heading { padding: 24px 20px; }
        .managed-page-description { font-size: .9375rem; }
        .managed-page-content { padding: 24px 20px 28px; font-size: .9375rem; line-height: 1.85; }
    }
</style>
@endpush

@section('content')
@php($displayDate = $page->content_updated_at ?? $page->first_published_at)
<section class="managed-page">
    <div class="site-container">
        <article class="site-card managed-page-card" aria-labelledby="managed-page-title">
            <header class="managed-page-heading">
                <h1 id="managed-page-title" class="managed-page-title">{{ \App\Support\SiteBrand::text($page->title, $siteName) }}</h1>
                @if($page->short_description)
                    <p class="managed-page-description">{{ \App\Support\SiteBrand::text($page->short_description, $siteName) }}</p>
                @endif
                @if($displayDate)
                    <div class="managed-page-date">
                        <x-student-icon name="calendar" />
                        <span>{{ $page->content_updated_at ? 'Son Güncelleme' : 'Yayınlanmış' }}:</span>
                        <time datetime="{{ $displayDate->toDateString() }}">{{ $displayDate->format('d.m.Y') }}</time>
                    </div>
                @endif
            </header>
            <div class="managed-page-content">
                {!! \App\Support\SiteBrand::html($page->content, $siteName) !!}
            </div>
        </article>
    </div>
</section>
@endsection
