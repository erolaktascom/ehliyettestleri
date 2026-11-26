<?php
namespace App\Support;

use App\Models\{SeoSetting, SeoRouteSetting, Exam, TopicTest, BlogPost, Lecture, Page, TopicCategory, LectureCategory, BlogCategory, TrafficSignCategory};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SiteSitemap
{
    public const SECTIONS = [
        'deneme-sinavlari' => ['label' => 'Deneme Sınavları', 'group' => 'İçerikler', 'model' => Exam::class],
        'konu-testleri' => ['label' => 'Konu Testleri', 'group' => 'İçerikler', 'model' => TopicTest::class, 'section' => 'topic_tests'],
        'animasyonlu-testler' => ['label' => 'Animasyonlu Sorular', 'group' => 'İçerikler', 'model' => TopicTest::class, 'section' => 'animated'],
        'blog-yazilari' => ['label' => 'Blog Yazıları', 'group' => 'İçerikler', 'model' => BlogPost::class],
        'ders-konulari' => ['label' => 'Konu Anlatımları', 'group' => 'İçerikler', 'model' => Lecture::class],
        'sayfalar' => ['label' => 'Sayfalar', 'group' => 'İçerikler', 'model' => Page::class],
        'soru-kategorileri' => ['label' => 'Soru Kategorileri', 'group' => 'Kategoriler', 'model' => TopicCategory::class],
        'ders-kategorileri' => ['label' => 'Konu Kategorileri', 'group' => 'Kategoriler', 'model' => LectureCategory::class],
        'blog-kategorileri' => ['label' => 'Blog Kategorileri', 'group' => 'Kategoriler', 'model' => BlogCategory::class],
        'isaret-kategorileri' => ['label' => 'İşaret Kategorileri', 'group' => 'Kategoriler', 'model' => TrafficSignCategory::class],
        'sabit-sayfalar' => ['label' => 'Ana Sayfa ve Liste Sayfaları', 'group' => 'Diğer Sayfalar', 'model' => null],
        'uyeler' => ['label' => 'Üye Profilleri', 'group' => 'Diğer Sayfalar', 'model' => null],
        'yazarlar' => ['label' => 'Yazar Sayfaları', 'group' => 'Diğer Sayfalar', 'model' => null],
    ];

    public readonly ?SeoSetting $setting;
    private $redirects;
    private $routeSettings;

    public function __construct()
    {
        $this->setting = SeoSetting::first();
        $this->redirects = SeoRedirectRules::activeRules();
        $this->routeSettings = SeoRouteSetting::all()->keyBy('route_key');
    }

    public function enabled(): bool { return (bool) ($this->setting?->sitemap_enabled ?? true); }
    public function sections(): array { return array_values(array_intersect($this->setting?->sitemap_sections ?? array_keys(self::SECTIONS), array_keys(self::SECTIONS))); }
    public function limit(): int { return max(1, min(1000, (int) ($this->setting?->sitemap_links_per_file ?? 200))); }
    public function base(): string { return PageSeo::baseUrl($this->setting); }
    public static function path(string $section, int $page = 1): string { return '/'.$section.'-sitemap'.($page > 1 ? '-'.$page : '').'.xml'; }

    private function records(string $section): iterable
    {
        if ($section === 'uyeler') {
            foreach (\App\Models\User::where('is_admin', false)->whereNotNull('profile_slug')->select('id', 'profile_slug')->lazyById(200) as $user) {
                yield [null, '/uye/'.rawurlencode($user->profile_slug), 'members.show'];
            }
            return;
        }
        $class = self::SECTIONS[$section]['model'] ?? null;
        // These URLs now open personal, noindex attempts rather than public landing pages.
        if (in_array($class, [Exam::class, TopicTest::class], true)) return;
        if (!$class) {
            $keys = $section === 'yazarlar' ? array_map(fn ($slug) => 'blog.author:'.$slug, array_keys(AuthorProfiles::catalog())) : array_keys(SeoPageCatalog::PAGES);
            foreach ($keys as $key) {
                $path = $section === 'yazarlar' ? '/yazar/'.rawurlencode(substr($key, 12)) : route($key, [], false);
                yield [$section === 'yazarlar' ? null : $this->routeSettings->get($key), $path, $key];
            }
            return;
        }
        $query = $class::query();
        if (in_array($class, [Exam::class, TopicTest::class, Lecture::class, BlogPost::class])) $query->where('is_published', true);
        // Guests and crawlers are redirected to registration above level 3.
        if ($class === Exam::class) $query->where('order', '<=', 3);
        if ($class === BlogPost::class) $query->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
        if ($class === Page::class) $query->where('status', 'published');
        if ($class === TopicCategory::class) $query->where('is_active', true)->whereHas('topicTests', fn ($q) => $q->where('section', 'topic_tests')->where('is_published', true));
        if ($class === TopicTest::class) $query->where('section', self::SECTIONS[$section]['section'])->whereHas('category', fn ($q) => $q->where('is_active', true));
        if ($class === Lecture::class) $query->has('category');
        foreach ($query->lazyById(200) as $record) {
            $path = AutomaticSlugRedirects::publicPath($record);
            if ($path) yield [$record, $path, ''];
        }
    }

    public function entries(string $section): iterable
    {
        if (!isset(self::SECTIONS[$section])) return;
        foreach ($this->records($section) as [$record, $path, $key]) {
            $model = $record instanceof SeoRouteSetting ? null : $record;
            $type = SeoTemplates::type($model, $key);
            if (PageSeo::indexValue($record, $type, $this->setting) === 'noindex') continue;
            $url = $this->base().$path;
            $canonical = trim((string) $record?->seo_canonical);
            if ($canonical !== '' && PageSeo::validCanonical($canonical) && $canonical !== $url) continue;
            // Match rules without dispatching a request or incrementing redirect counters.
            if (SeoRedirectRules::resolve($path, $this->redirects)) continue;
            if (strlen($url) >= 2048) continue;
            $lastmod = null;
            if ($model) {
                $attributes = $model->getAttributes();
                $date = array_key_exists('content_updated_at', $attributes)
                    ? ($model->content_updated_at ?: ($model->published_at ?: $model->first_published_at))
                    : $model->updated_at;
                if ($date && Carbon::parse($date)->lte(now())) $lastmod = Carbon::parse($date)->toAtomString();
            }
            yield ['loc' => $url, 'lastmod' => $lastmod];
        }
    }

    public function counts(): array
    {
        $counts = [];
        foreach (self::SECTIONS as $section => $config) $counts[$section] = iterator_count($this->entries($section));
        return $counts;
    }

    public function indexEntries(): iterable
    {
        foreach ($this->sections() as $section) {
            $count = iterator_count($this->entries($section));
            for ($page = 1; $page <= (int) ceil($count / $this->limit()); $page++) yield ['loc' => $this->base().self::path($section, $page)];
        }
    }

    public static function robotsBody(?string $body): string
    {
        $lines = preg_split('/\R/u', $body ?? "User-agent: *\nAllow: /\nDisallow: /admin/");
        return trim(implode("\n", array_filter($lines, fn ($line) => !preg_match('/^\s*Sitemap\s*:/i', $line))));
    }

    public function robots(): string
    {
        $body = self::robotsBody($this->setting?->robots_txt);
        if ($this->enabled()) $body .= "\n\nSitemap: ".$this->base().'/sitemap_index.xml';
        return trim($body)."\n";
    }
}
