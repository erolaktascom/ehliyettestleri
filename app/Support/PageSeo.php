<?php
namespace App\Support;

use App\Models\SeoRouteSetting;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PageSeo
{
    public static function indexValue(?Model $record, ?string $type, ?\App\Models\SeoSetting $setting): string
    {
        if (in_array($record?->seo_index, ['index', 'noindex'], true)) return $record->seo_index;
        return $type ? SeoTemplates::values($setting?->content_rules)[$type]['index'] : 'index';
    }

    public static function baseUrl(?\App\Models\SeoSetting $setting): string
    {
        $base = rtrim((string) config('app.url'), '/');
        return self::validCanonical($base) ? $base : rtrim(config('app.url'), '/');
    }

    public static function validCanonical(?string $value): bool
    {
        if ($value === null || trim($value) === '') return true;
        $parts = parse_url($value);
        return filter_var($value, FILTER_VALIDATE_URL) !== false && $parts
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['fragment']);
    }

    public static function metadata(array $data, string $defaultTitle, string $defaultDescription, string $forcedRobots = '', bool $error = false): array
    {
        $request = request();
        $route = $request->route()?->getName() ?? '';
        $setting = $data['seoSetting'] ?? null;
        $brand = SiteBrand::name($data['siteSetting'] ?? null);
        $model = match ($route) {
            'exams.play' => $request->route('exam'),
            'topic-tests.play', 'animated-questions.play' => $data['test'] ?? $request->route('test'),
            'blog.show' => $data['post'] ?? $request->route('post'),
            'lectures.show' => $data['lecture'] ?? $request->route('lecture'),
            'page.show', 'about' => $data['page'] ?? null,
            'topic-tests.category', 'lectures.category', 'traffic-signs.learn', 'blog.category' => $data['category'] ?? $request->route('category'),
            default => null,
        };
        if (!$model instanceof Model) $model = null;
        $key = $route;
        if ($route === 'blog.index.page') $key = 'blog.index';
        if ($route === 'blog.author') $key .= ':'.$request->route('slug');
        $record = $model ?? (isset(SeoPageCatalog::PAGES[$key])
            ? SeoRouteSetting::where('route_key', $key)->first() : null);

        $playPage = in_array($route, ['exams.play', 'topic-tests.play', 'animated-questions.play'], true);
        $private = $playPage || isset($data['attempt']) || in_array($route, ['search', 'login', 'register', 'dashboard', 'profile', 'exams.result'], true)
            || str_starts_with($route, 'password.') || str_starts_with($route, 'student.') || str_starts_with($route, 'auth.');
        // Test titles/descriptions still use content rules; personal attempts stay noindex below.
        $type = !$error && (!$private || $playPage) ? SeoTemplates::type($model, $key) : null;
        $rule = $type ? SeoTemplates::values($setting?->content_rules)[$type] : ['title' => '', 'description' => '', 'index' => 'index'];
        $context = SeoTemplates::context($model, $key, $data, $defaultDescription);
        $index = self::indexValue($record, $type, $setting);
        foreach ($data as $value) {
            if ($value instanceof Paginator && $value->currentPage() > 1) {
                $index = $setting?->paginationIndex() ?? 'index';
                break;
            }
        }
        $robots = $error ? 'noindex, follow' : ($private ? ($route === 'search' ? 'noindex, follow' : 'noindex, nofollow') : ($forcedRobots ?: ($index.', follow')));
        if ($error) $record = $model = null;

        $title = SiteBrand::text(trim((string) $record?->seo_title), $brand);
        if ($title === '') $title = SeoTemplates::expand($rule['title'], $context);
        if ($title === '') {
            $title = $defaultTitle;
        }
        $description = SiteBrand::text(trim((string) $record?->seo_description), $brand);
        if ($description === '') $description = SeoTemplates::expand($rule['description'], $context);
        if ($description === '') {
            $description = $model?->summary ?: ($model?->short_description ?: ($model?->description ?: $defaultDescription));
        }
        $title = SiteBrand::text($title, $brand);
        $description = SiteBrand::text($description, $brand);
        $description = Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $description))), 1000, '');

        $base = self::baseUrl($setting);
        $path = $model ? AutomaticSlugRedirects::publicPath($model) : null;
        if (!$path) $path = '/'.ltrim($request->path(), '/');
        // Preserve pagination as a distinct page while ignoring tracking/filter noise.
        $page = $request->query('page');
        $posts = $data['posts'] ?? null;
        if ($posts instanceof BlogPaginator) {
            $path = $posts->url($posts->currentPage());
        } elseif (is_scalar($page) && ctype_digit((string) $page) && (int) $page > 1) {
            $path .= (str_contains($path, '?') ? '&' : '?').'page='.(int) $page;
        }
        $customCanonical = trim((string) $record?->seo_canonical);
        $canonical = $customCanonical !== '' && self::validCanonical($customCanonical) ? $customCanonical : $base.$path;
        if ($error || $private) $canonical = null;
        return compact('title', 'description', 'canonical', 'robots');
    }
}
