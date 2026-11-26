<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Support\BlogPaginator;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request, ?int $page = null)
    {
        abort_if($request->has('category'), 404);

        return $this->listing(null, $page);
    }

    public function category(BlogCategory $category, ?int $page = null)
    {
        return $this->listing($category, $page);
    }

    private function listing(?BlogCategory $category, ?int $page)
    {
        $categories = BlogCategory::withCount(['posts' => fn ($q) => $q->visible()])->get();
        $query = BlogPost::with('creator:id,name,avatar_path')->visible()->latest('published_at')->latest('id');
        if ($category) $query->whereBelongsTo($category, 'category');
        $posts = BlogPaginator::forListing($query, $category ? 'blog.category' : 'blog.index',
            $category ? ['category' => $category->slug] : [], $page);
        return view('pages.blog.index', compact('posts', 'categories', 'category'));
    }

    public function show(BlogPost $post)
    {
        abort_unless($post->isContentPublished(), 404);
        BlogPost::syncPublicationDates();
        $post->refresh();

        BlogPost::withoutTimestamps(fn () => $post->increment('views_count'));
        $relatedPosts = BlogPost::where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->visible()
            ->limit(3)
            ->get();

        return view('pages.blog.show', compact('post', 'relatedPosts'));
    }

    public function author(string $slug, ?int $page = null)
    {
        $publicProfile = \App\Support\AuthorProfiles::catalog()[$slug] ?? null;
        abort_unless($publicProfile, 404);
        $query = \App\Support\AuthorProfiles::publishedPosts()->with('category');
        if ($publicProfile['id']) $query->where('created_by', $publicProfile['id']);
        else $query->whereDoesntHave('creator')->where('author_name', $publicProfile['name']);
        $posts = BlogPaginator::forListing($query->latest('published_at')->latest('id'), 'blog.author', ['slug' => $slug], $page);
        $authorName = $publicProfile['name'];
        return view('pages.blog.author', compact('publicProfile', 'authorName', 'posts'));
    }
}
