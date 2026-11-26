@extends('layouts.app')
@section('title', $authorName.' Yazıları - ' . $siteName)
@section('meta_description', $authorName.' yazar profili ve yayımlanmış blog yazıları.')
<x-blog-styles />
@section('content')
<div class="site-page-content blog-catalog">
    <div class="site-container">
        <nav aria-label="Sayfa yolu" class="flex flex-wrap gap-2 text-xs text-muted mb-5">
            <a href="{{ route('blog.index') }}" class="hover:text-brand-700">Blog</a><span aria-hidden="true">›</span><span aria-current="page">{{ $authorName }}</span>
        </nav>
        <x-public-profile-card :profile="$publicProfile" kind="Yazar" :post-count="$posts->total()" />
        <section class="mt-9" aria-labelledby="author-posts">
            <div class="flex items-center justify-between gap-4 mb-5">
                <h2 id="author-posts" class="text-xl font-extrabold text-ink">Yazıları</h2>
                <span class="text-xs text-muted">{{ $posts->total() }} yazı</span>
            </div>
            <div class="blog-post-grid">
                @forelse($posts as $post)
                    <x-blog-post-card :post="$post" heading="h3" />
                @empty
                    <div class="site-card blog-post-empty"><p class="text-sm text-muted">Henüz yayımlanmış bir yazı bulunmuyor.</p></div>
                @endforelse
            </div>
            <div class="mt-6">{{ $posts->links() }}</div>
        </section>
    </div>
</div>
@endsection