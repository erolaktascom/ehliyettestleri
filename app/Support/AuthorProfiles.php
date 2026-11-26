<?php
namespace App\Support;

use App\Models\{BlogPost, User};
use Illuminate\Support\Str;

class AuthorProfiles
{
    public static function publishedPosts()
    {
        return BlogPost::where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public static function catalog(): array
    {
        $profiles = [];
        $authors = User::whereNotNull('profile_slug')->where(fn ($q) => $q->where('is_admin', true)
            ->orWhereHas('blogPosts', fn ($posts) => $posts->where('is_published', true)
                ->where(fn ($dates) => $dates->whereNull('published_at')->orWhere('published_at', '<=', now()))))
            ->get(['id', 'name', 'profile_slug', 'biography', 'avatar_path', 'created_at']);
        foreach ($authors as $author) {
            $profiles[$author->profile_slug] = self::data($author);
        }
        // Unowned historic articles remain available without inventing an account owner.
        $names = self::publishedPosts()->whereDoesntHave('creator')->distinct()->pluck('author_name')->filter();
        foreach ($names as $name) {
            $slug = self::legacySlug($name);
            $profiles[$slug] = ['id' => null, 'name' => $name, 'slug' => $slug,
                'initials' => User::make(['name' => $name])->initials, 'biography' => '', 'avatar' => null, 'joined' => null];
        }
        return $profiles;
    }

    public static function legacySlug(string $name): string
    {
        $base = Str::slug($name) ?: 'yazar';
        return User::where('profile_slug', $base)->exists() ? $base.'-'.substr(sha1($name), 0, 10) : $base;
    }

    public static function data(User $user): array
    {
        // An explicit public projection: no contact, login or exam data.
        return ['id' => $user->id, 'name' => $user->name, 'slug' => $user->profile_slug,
            'initials' => $user->initials, 'biography' => $user->biography ?: '',
            'avatar' => $user->avatar_url, 'joined' => $user->created_at];
    }
}