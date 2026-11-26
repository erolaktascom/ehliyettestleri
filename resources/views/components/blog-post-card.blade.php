@props(['post', 'heading' => 'h2', 'showAuthorAvatar' => false])
@php($date = $post->published_at ?? $post->first_published_at)
<article class="blog-post-card">
    <div class="blog-post-cover" aria-hidden="true">
        @if($post->featured_image_url)
            <img src="{{ $post->featured_image_url }}" alt="" loading="lazy" width="640" height="360">
        @else
            {{ mb_substr($post->title, 0, 1) }}
        @endif
    </div>
    <div class="blog-post-body">
        @if($post->category)<span class="blog-post-category">{{ $post->category->name }}</span>@endif
        <{{ $heading }} class="blog-post-title"><a class="blog-post-link" href="{{ route('blog.show', $post) }}">{{ $post->title }}</a></{{ $heading }}>
        @if($post->summary)<p class="blog-post-summary">{{ $post->summary }}</p>@endif
    </div>
    <div class="blog-post-footer">
        <div @class(['blog-post-author', 'with-avatar' => $showAuthorAvatar])>
            @if($showAuthorAvatar)
                @php($author = $post->creator)
                @if($author?->avatar_url)
                    <img class="blog-author-avatar" src="{{ $author->avatar_url }}" alt="" width="32" height="32" loading="lazy">
                @else
                    <span class="blog-author-avatar" aria-hidden="true">{{ $author?->initials ?? $post->author_initials }}</span>
                @endif
                <strong>{{ $author?->name ?? $post->author_name }}</strong>
            @else
                <strong>{{ $post->author_name }}</strong>
                @if($date)<time datetime="{{ $date->toDateString() }}">{{ $date->format('d.m.Y') }}</time>@endif
            @endif
        </div>
        <span class="blog-post-read" aria-hidden="true">Yazıyı oku <x-student-icon name="arrow" /></span>
    </div>
</article>
