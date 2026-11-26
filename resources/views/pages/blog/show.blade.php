@extends('layouts.app')

@section('title', $post->title . ' - ' . $siteName . ' Blog')

@push('styles')
<style>
    .blog-article-content { color: #475569; font-size: 1rem; line-height: 1.9; overflow-wrap: anywhere; }
    .blog-article-content > :first-child { margin-top: 0; }
    .blog-article-content > :last-child { margin-bottom: 0; }
    .blog-article-content :is(h1,h2,h3,h4) { color: #111827; font-weight: 800; line-height: 1.4; margin: 2rem 0 .85rem; letter-spacing: -.025em; }
    .blog-article-content :is(h1,h2) { font-size: 1.45rem; }
    .blog-article-content h3 { font-size: 1.2rem; }
    .blog-article-content h4 { font-size: 1.05rem; }
    .blog-article-content p { margin-bottom: 1.15rem; }
    .blog-article-content :is(ul,ol) { padding-left: 1.5rem; margin: 1rem 0 1.5rem; }
    .blog-article-content ul { list-style: disc; }
    .blog-article-content ol { list-style: decimal; }
    .blog-article-content li { padding-left: .25rem; margin: .4rem 0; }
    .blog-article-content a { color: #2339d2; text-decoration: underline; text-underline-offset: 3px; }
    .blog-article-content :is(strong,b) { color: #1e293b; font-weight: 700; }
    .blog-article-content blockquote { border-left: 3px solid #4871f4; background: #f0f4ff; padding: 1rem 1.25rem; margin: 1.5rem 0; border-radius: 0 .75rem .75rem 0; }
    .blog-article-content blockquote p:last-child { margin-bottom: 0; }
    .blog-article-content :is(img,video) { max-width: 100%; height: auto; border-radius: .75rem; margin: 1.5rem auto; }
    .blog-article-content iframe { max-width: 100%; width: 100%; aspect-ratio: 16/9; height: auto; border: 0; border-radius: .75rem; }
    .blog-article-content table { display: block; width: 100%; max-width: 100%; overflow-x: auto; border-collapse: collapse; margin: 1.5rem 0; }
    .blog-article-content :is(th,td) { border: 1px solid #e2e8f0; padding: .65rem .85rem; text-align: left; }
    .blog-article-content th { background: #f8fafc; color: #1e293b; }
    .blog-article-content pre { overflow-x: auto; background: #f8fafc; padding: 1rem; border-radius: .75rem; margin: 1rem 0; }
    .blog-article-content hr { border-color: #e2e8f0; margin: 2rem 0; }
    @media (min-width: 640px) { .blog-article-content { font-size: 1.05rem; } }
    .blog-reading-page { padding-top: 20px; padding-bottom: 56px; }
    .blog-reading-hero { padding: 36px 40px; margin-bottom: 24px; background: #fff; border-top: 4px solid #293be7; }
    .blog-reading-title { max-width: 1000px; color: #0f172a; font-size: clamp(1.75rem, 3.5vw, 2.75rem); font-weight: 800; letter-spacing: -.035em; line-height: 1.2; overflow-wrap: anywhere; }
    .blog-reading-summary { max-width: 82ch; margin-top: 18px; color: #64748b; font-size: 1rem; line-height: 1.8; overflow-wrap: anywhere; }
    .blog-reading-divider { border-top: 1px solid #d6dde6; }
    .blog-reading-meta { margin-top: 24px; padding-top: 20px; display: flex; align-items: center; flex-wrap: wrap; column-gap: 28px; row-gap: 16px; }
    .blog-reading-dates { display: flex; flex-wrap: wrap; column-gap: 28px; row-gap: 12px; font-size: .75rem; }
    .blog-reading-date { display: grid; gap: 4px; color: #64748b; }
    .blog-reading-date time { color: #334155; font-weight: 600; }
    .blog-share { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-inline-start: auto; color: #475569; font-size: .875rem; }
    .blog-share-control { display: inline-grid; place-items: center; width: 36px; height: 36px; border-radius: 50%; background: var(--share-color); color: #fff; }
    .blog-share-control svg { width: 17px; height: 17px; }
    .blog-share-control.share-whatsapp { --share-color: #25d366; }
    .blog-share-control.share-copy { --share-color: #2563eb; }
    .blog-share-control.share-native { --share-color: #7c3aed; }
    .blog-share-control:hover { filter: brightness(.91); }
    .blog-share-control:focus-visible { outline: 2px solid #334155; outline-offset: 3px; }
    .blog-reading-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; align-items: start; gap: 24px; }
    .blog-reading-body { padding: 36px 40px; }
    .blog-reading-footer { margin: 0 40px; padding: 20px 0; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; }
    .blog-reading-page .blog-article-content { color: #475569; font-size: 1.0625rem; line-height: 1.9; }
    .blog-reading-page .blog-article-content :is(h2,h3) { margin-top: 2.25rem; }
    .blog-reading-page .blog-article-content > :first-child { margin-top: 0; }
    .blog-reading-page .blog-article-content a:focus-visible { outline: 3px solid #a5b4fc; outline-offset: 3px; }
    .blog-reading-side { display: grid; gap: 20px; min-width: 0; }
    .blog-reading-side-card { padding: 24px; }
    .blog-reading-author { padding-bottom: 20px; border-bottom: 1px solid #edf0f6; }
    @media (max-width: 1023px) {
        .blog-reading-grid { grid-template-columns: minmax(0, 1fr); }
        .blog-reading-side { grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; }
    }
    @media (max-width: 639px) {
        .blog-reading-page { padding-top: 16px; padding-bottom: 32px; }
        .blog-reading-hero { padding: 24px 20px; margin-bottom: 20px; }
        .blog-reading-summary { font-size: .9375rem; margin-top: 14px; }
        .blog-reading-meta { align-items: flex-start; flex-direction: column; }
        .blog-share { margin-inline-start: 0; }
        .blog-reading-body { padding: 24px 20px; }
        .blog-reading-footer { margin-inline: 20px; }
        .blog-reading-grid { gap: 20px; }
        .blog-reading-side { grid-template-columns: minmax(0, 1fr); }
        .blog-reading-side-card { padding: 20px; }
        .blog-reading-page .blog-article-content { font-size: 1rem; line-height: 1.85; }
    }
</style>
@endpush

@section('content')
@php
    $publicationDate = $post->published_at ?? $post->first_published_at;
    $readingMinutes = max(1, (int) ceil(preg_match_all('/\S+/u', html_entity_decode(strip_tags($post->content ?? ''), ENT_QUOTES, 'UTF-8')) / 200));
    $authorAvatar = $post->creator?->avatar_url;
    $shareUrl = route('blog.show', $post);
@endphp
<div class="site-container blog-reading-page">
    <nav aria-label="Sayfa yolu" class="flex items-center gap-2 text-xs text-muted mb-5 min-w-0">
        <a href="{{ route('home') }}" class="shrink-0 hover:text-brand-700">Ana Sayfa</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('blog.index') }}" class="shrink-0 hover:text-brand-700">Blog</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page" class="truncate font-semibold text-slate-600">{{ $post->title }}</span>
    </nav>

<header class="site-card blog-reading-hero">
                <div class="flex flex-wrap items-center gap-3 mb-5">
                    @if($post->category)
                        <a href="{{ route('blog.category', $post->category->slug) }}" class="stitch-pill hover:bg-brand-100 transition">{{ $post->category->name }}</a>
                    @endif
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-muted">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                        {{ $readingMinutes }} dk okuma
                    </span>
                </div>
                <h1 id="blog-reading-title" class="blog-reading-title">{{ $post->title }}</h1>
                @if($post->summary)
                    <p class="blog-reading-summary">{{ $post->summary }}</p>
                @endif
                <div class="blog-reading-meta blog-reading-divider">
                    <div class="blog-reading-dates">
                        @if($publicationDate)
                            <span class="blog-reading-date"><span>Yayınlanma</span><time datetime="{{ $publicationDate->toAtomString() }}">{{ $publicationDate->format('d.m.Y') }}</time></span>
                        @endif
                        @if($post->content_updated_at)
                            <span class="blog-reading-date"><span>Son güncelleme</span><time datetime="{{ $post->content_updated_at->toAtomString() }}">{{ $post->content_updated_at->format('d.m.Y') }}</time></span>
                        @endif
                    </div>
                </div>
            </header>
    <div class="blog-reading-grid">
        <article class="site-card min-w-0 overflow-hidden" aria-labelledby="blog-reading-title">


            @if($post->featured_image_url)
                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" width="1200" height="675" class="w-full aspect-video object-cover">
            @endif
            <div class="blog-article-content blog-reading-body">
                {!! $post->content !!}
            </div>

            <footer class="blog-reading-footer blog-reading-divider">
                <span class="text-xs text-muted">{{ number_format($post->views_count, 0, ',', '.') }} görüntülenme</span>
                <div class="blog-share" role="group" aria-label="Yazıyı paylaş" x-data="{ copied: false, manual: false, shareFallback: false }">
                    <span>Paylaş</span>
                    <a class="blog-share-control share-whatsapp" href="https://wa.me/?text={{ rawurlencode($post->title . ' ' . $shareUrl) }}"
                       target="_blank" rel="noopener noreferrer" aria-label="WhatsApp'ta paylaş" title="WhatsApp'ta paylaş">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 11.8a8.8 8.8 0 0 1-13.1 7.7L3 20.8l1.3-4.6A8.8 8.8 0 1 1 20.8 11.8Z"/><path d="m9 7.4 1.2 2.5-1 1.1c.8 1.7 2 2.9 3.8 3.7l1.1-1.2 2.5 1.1c-.1 1.5-1 2.3-2.4 2.1-3.7-.6-7-4-7.5-7.3-.1-1.1.8-2.3 2.3-2Z"/></svg>
                    </a>
                    <button type="button" class="blog-share-control share-copy"
                            aria-label="Bağlantıyı kopyala" title="Bağlantıyı kopyala"
                            @click="async () => { try {
                                await navigator.clipboard.writeText($refs.url.value);
                                copied = true; manual = false;
                            } catch {
                                copied = false; manual = true;
                                $nextTick(() => { $refs.url.focus(); $refs.url.select(); });
                            } }">
                        <svg x-show="!copied" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="m10 13 4-4m-5 2-2 2a3.5 3.5 0 0 0 5 5l3-3m-1-3 2-2a3.5 3.5 0 0 0-5-5l-3 3"/></svg>
                        <x-student-icon name="check" class="size-5" x-cloak x-show="copied" />
                    </button>
                    <button type="button" class="blog-share-control share-native" title="Paylaş" aria-label="Paylaş"
                            data-title="{{ $post->title }}" @click="async () => {
                                copied = false; manual = false;
                                if (!navigator.share) { shareFallback = !shareFallback; return; }
                                shareFallback = false;
                                try { await navigator.share({ title: $el.dataset.title, url: $refs.url.value }); }
                                catch (error) { shareFallback = error.name !== 'AbortError'; }
                            }">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4m-6.8 7 6.8 4"/></svg>
                    </button>
                    <a x-cloak x-show="shareFallback" href="mailto:?subject={{ rawurlencode($post->title) }}&amp;body={{ rawurlencode($shareUrl) }}" class="text-sm font-bold text-brand-700 underline">E-postayla paylaş</a>
                    <span role="status" x-text="copied ? 'Kopyalandı' : (manual ? 'Bağlantıyı seçip kopyalayabilirsin.' : '')"></span>
                    <input x-cloak x-show.immediate="manual" x-ref="url" readonly aria-label="Yazı bağlantısı" value="{{ $shareUrl }}" class="w-full rounded-lg border border-slate-200 p-2">
                </div>
            </footer>
        </article>

        <aside class="blog-reading-side" aria-label="Yazar ve diğer içerikler">
            <section class="site-card blog-reading-side-card" aria-labelledby="blog-author-heading">
                <h2 id="blog-author-heading" class="text-xs font-bold uppercase tracking-wider text-muted mb-5">Yazar Hakkında</h2>
                <div class="flex items-center gap-3">
                    @if($authorAvatar)
                        <img src="{{ $authorAvatar }}" alt="" class="size-12 rounded-2xl object-cover" width="48" height="48" loading="lazy">
                    @else
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-800 font-extrabold">{{ $post->author_initials }}</span>
                    @endif
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-ink break-words">{{ $post->author_name }}</h3>
                        <p class="text-xs text-muted mt-1">Yazar</p>
                    </div>
                </div>
                @if($post->creator?->biography)
                    <p class="text-sm text-muted mt-4 leading-relaxed">{{ Str::limit($post->creator->biography, 250) }}</p>
                @endif
                <a href="{{ $post->author_url }}" class="blog-reading-divider mt-5 flex items-center justify-between gap-2 pt-4 text-sm font-bold text-brand-700 hover:text-brand-900">Yazarın tüm yazıları <x-student-icon name="arrow" class="size-4 shrink-0" /></a>
            </section>

            @if($relatedPosts->isNotEmpty())
                <section class="site-card blog-reading-side-card" aria-labelledby="blog-related-heading">
                    <h2 id="blog-related-heading" class="text-base font-extrabold text-ink mb-1">İlgili Yazılar</h2>
                    <div class="divide-y divide-slate-100">
                        @foreach($relatedPosts as $relatedPost)
                            <a href="{{ route('blog.show', $relatedPost) }}" class="group block py-4 last:pb-0">
                                <h3 class="text-sm font-bold text-slate-700 leading-relaxed group-hover:text-brand-700">{{ $relatedPost->title }}</h3>
                                @if($relatedPost->summary)<p class="text-xs text-muted mt-1.5 line-clamp-2 leading-relaxed">{{ $relatedPost->summary }}</p>@endif
                                <span class="inline-block text-xs text-brand-700 font-bold mt-2"><x-action-label icon="book">Yazıyı oku</x-action-label></span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-2xl border border-brand-100 bg-brand-50 blog-reading-side-card">
                <h2 class="text-base font-extrabold text-brand-950">Hazırlığa devam et</h2>
                <p class="text-sm text-slate-600 leading-relaxed mt-2">Öğrendiklerini deneme sınavlarıyla pekiştir.</p>
                <a href="{{ route('exams.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-700 px-4 py-3 text-sm font-bold text-white hover:bg-brand-800 transition">Deneme Sınavları <x-student-icon name="arrow" class="size-4 shrink-0" /></a>
            </section>
            <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-brand-700 px-1"><x-action-label icon="arrow-left">Tüm blog yazıları</x-action-label></a>
        </aside>
    </div>
</div>
@endsection
