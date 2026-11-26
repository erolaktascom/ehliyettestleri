<?php
namespace App\Support;

use App\Models\{Exam, TopicTest, BlogPost, Lecture, Page, TopicCategory, LectureCategory, BlogCategory, TrafficSignCategory};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SeoTemplates
{
    public const CATEGORY_TYPES = [
        'topic_categories' => 'Soru Kategorileri',
        'lecture_categories' => 'Konu Kategorileri',
        'blog_categories' => 'Blog Kategorileri',
        'traffic_sign_categories' => 'İşaret Kategorileri',
    ];
    public const TYPES = [
        'exams' => 'Deneme Sınavları',
        'topic_tests' => 'Konu Testleri',
        'topic_categories' => self::CATEGORY_TYPES['topic_categories'],
        'lectures' => 'Konu Anlatımları',
        'lecture_categories' => self::CATEGORY_TYPES['lecture_categories'],
        'blog_posts' => 'Blog Yazıları',
        'blog_categories' => self::CATEGORY_TYPES['blog_categories'],
        'traffic_sign_categories' => self::CATEGORY_TYPES['traffic_sign_categories'],
        'authors' => 'Yazar Profilleri',
        'members' => 'Üye Profilleri',
    ];
    public const DEFAULT_TITLES = [
        'exams' => '%baslik% Ehliyet Deneme Sınavı | %site_adi%',
        'topic_tests' => '%kategori% %baslik% Testi | %site_adi%',
    ];
    public const TOKENS = ['%baslik%', '%site_adi%', '%ozet%', '%kategori%', '%yil%', '%ad%', '%kullanici_adi%'];

    public static function values(?array $saved = null): array
    {
        $rules = [];
        foreach (self::TYPES as $key => $label) {
            $value = $saved[$key] ?? (isset(self::CATEGORY_TYPES[$key]) ? ($saved['categories'] ?? []) : []);
            $rules[$key] = [
                'title' => is_string($value['title'] ?? null) ? $value['title'] : (self::DEFAULT_TITLES[$key] ?? ''),
                'description' => is_string($value['description'] ?? null) ? $value['description'] : '',
                'index' => ($value['index'] ?? '') === 'noindex' ? 'noindex' : 'index',
            ];
        }
        return $rules;
    }

    public static function type(?Model $model, string $key): ?string
    {
        return match (true) {
            $model instanceof Exam => 'exams', $model instanceof TopicTest => 'topic_tests',
            $model instanceof BlogPost => 'blog_posts', $model instanceof Lecture => 'lectures',
            $model instanceof Page => null,
            $model instanceof TopicCategory => 'topic_categories',
            $model instanceof LectureCategory => 'lecture_categories',
            $model instanceof BlogCategory => 'blog_categories',
            $model instanceof TrafficSignCategory => 'traffic_sign_categories',
            str_starts_with($key, 'blog.author:') => 'authors',
            $key === 'members.show' => 'members',
            default => null,
        };
    }

    public static function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
    }

    public static function context(?Model $model, string $key, array $data, string $fallbackDescription): array
    {
        $category = $model && method_exists($model, 'category') ? $model->category?->name : '';
        if (isset(self::CATEGORY_TYPES[self::type($model, $key) ?? ''])) $category = $model?->name;
        $summary = $model?->summary ?: ($model?->short_description ?: ($model?->description ?: ($model?->content ?: $fallbackDescription)));
        $profile = $data['publicProfile'] ?? [];
        if (!empty($profile['biography'])) $summary = $profile['biography'];
        return [
            '%ad%' => (string) ($profile['name'] ?? ($data['authorName'] ?? '')),
            '%kullanici_adi%' => (string) ($profile['slug'] ?? ''),
            '%baslik%' => (string) ($model?->title ?: ($model?->name ?: ($data['authorName'] ?? ($profile['name'] ?? (SeoPageCatalog::PAGES[$key] ?? ''))))),
            '%site_adi%' => SiteBrand::name($data['siteSetting'] ?? null),
            '%ozet%' => Str::limit(self::clean((string) $summary), 300, ''),
            '%kategori%' => (string) $category, '%yil%' => now()->format('Y'),
        ];
    }

    public static function expand(string $template, array $context): string
    {
        // One pass: content is text, never another template or executable markup.
        return self::clean(strtr($template, $context));
    }

    public static function validTemplate(string $template): bool
    {
        preg_match_all('/%[^%\s]+%/u', $template, $matches);
        return array_diff($matches[0], self::TOKENS) === [];
    }

    /** Preview only the general template, using actual content; never invent fallback metadata. */
    public static function preview(string $type, array $rule, string $brand): array
    {
        $class = match ($type) {
            'exams' => Exam::class, 'topic_tests' => TopicTest::class,
            'blog_posts' => BlogPost::class, 'lectures' => Lecture::class,
            'topic_categories' => TopicCategory::class, 'lecture_categories' => LectureCategory::class,
            'blog_categories' => BlogCategory::class, 'traffic_sign_categories' => TrafficSignCategory::class,
            default => null,
        };
        $model = $class ? $class::orderBy('id')->first() : null;
        $profile = null;
        if ($type === 'authors') $profile = array_values(AuthorProfiles::catalog())[0] ?? null;
        if ($type === 'members') {
            $user = \App\Models\User::where('is_admin', false)->whereNotNull('profile_slug')->orderBy('id')->first();
            if ($user) $profile = AuthorProfiles::data($user);
        }
        $sample = $model?->title ?: ($model?->name ?: ($profile['name'] ?? null));
        if (!$sample) return ['sample' => null, 'title' => '', 'description' => ''];
        $site = new \App\Models\SiteSetting(['site_name' => $brand]);
        $context = self::context($model, $type === 'members' ? 'members.show' : '', ['siteSetting' => $site, 'publicProfile' => $profile ?? []], '');
        return ['sample' => $sample,
            'title' => self::expand($rule['title'] ?? '', $context),
            'description' => self::expand($rule['description'] ?? '', $context)];
    }
}