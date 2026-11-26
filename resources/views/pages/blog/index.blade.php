@extends('layouts.app')
@section('title', $category ? $category->name . ' - ' . $siteName : $siteName . ' Blog - Bilgi ve Rehberler')
<x-blog-styles />
@section('content')
<section class="blog-catalog" aria-labelledby="blog-title">
    <div class="site-container">
        <header class="site-card blog-catalog-heading">
            <h1 id="blog-title">{{ $category?->name ?? ($siteName . ' Blog') }}</h1>
            <p>Sınava hazırlanırken ve direksiyon başına geçtikten sonra işinize yarayacak güncel rehberler ve ipuçları.</p>
        </header>
        <nav class="blog-filters" aria-label="Blog kategorileri">
            <a href="{{ route('blog.index') }}" class="blog-filter" @if(!$category) aria-current="page" @endif>Tümü</a>
            @foreach($categories as $cat)
                <a href="{{ route('blog.category', $cat->slug) }}" class="blog-filter" @if($cat->is($category)) aria-current="page" @endif>
                    {{ $cat->name }} <span class="blog-filter-count">({{ $cat->posts_count }})</span>
                </a>
            @endforeach
        </nav>
        <div class="blog-post-grid">
            @forelse($posts as $post)
                <x-blog-post-card :post="$post" :show-author-avatar="$category === null" />
            @empty
                <div class="site-card blog-post-empty">Bu kategoride henüz yazı yayınlanmadı.</div>
            @endforelse
        </div>
        <div class="mt-8">{{ $posts->links() }}</div>
    </div>
</section>
@endsection
