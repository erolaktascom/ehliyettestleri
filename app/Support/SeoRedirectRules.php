<?php

namespace App\Support;

use App\Models\SeoRedirect;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SeoRedirectRules
{
    public const MATCH_TYPES = [
        'exact' => 'Tam eşleşme', 'contains' => 'İçerir', 'starts_with' => 'Şununla başlar',
        'ends_with' => 'Şununla biter', 'regex' => 'Düzenli ifade',
    ];

    public static function protectedPath(string $path): bool
    {
        if (preg_match('~^/(?:robots\.txt|sitemap(?:_index)?\.xml|sitemap-style\.xsl|[a-z-]+-sitemap(?:-[0-9]+)?\.xml)$~i', $path)) return true;
        return (bool) preg_match('~^/(?:admin|livewire|api|auth|login|logout|register|settings|sifremi-unuttum|sifre-yenile|up|sinav/oturum|konu-testleri/oturum)(?:/|$)~i', rawurldecode($path));
    }

    public static function normalize(string $url, bool $source = false): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url) || preg_match('/[\x00-\x1f\x7f\\\\]/', rawurldecode($url))) {
            throw new InvalidArgumentException('Geçerli bir adres girin; boşluk veya kontrol karakteri kullanmayın.');
        }
        $parts = parse_url($url);
        if (!$parts || isset($parts['user']) || isset($parts['pass'])) throw new InvalidArgumentException('Geçersiz URL.');
        $external = false;
        if (isset($parts['scheme'])) {
            if (!in_array(strtolower($parts['scheme']), ['http', 'https']) || !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Yalnız http/https adresleri kullanılabilir.');
            }
            $authority = strtolower($parts['host'] ?? '').':'.($parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80));
            $local = [config('app.url'), request()->getSchemeAndHttpHost()];
            $external = !collect($local)->contains(function ($base) use ($authority) {
                $base = parse_url($base);
                return strtolower($base['host'] ?? '').':'.($base['port'] ?? (($base['scheme'] ?? '') === 'https' ? 443 : 80)) === $authority;
            });
            if ($source && $external) throw new InvalidArgumentException('Eski adres bu siteye ait olmalıdır; /eski-adres biçimini kullanabilirsiniz.');
        } elseif (!str_starts_with($url, '/') || str_starts_with($url, '//') || isset($parts['host'])) {
            throw new InvalidArgumentException('Site içi adres / ile başlamalıdır.');
        }
        $decoded = rawurldecode($parts['path'] ?? '/');
        if (str_starts_with($decoded, '//') || preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $decoded)) {
            throw new InvalidArgumentException('Geçersiz adres yolu.');
        }
        if ($source && isset($parts['fragment'])) throw new InvalidArgumentException('Eski adreste # bölümü kullanılamaz.');
        if ($external) return $url;
        $path = rtrim(implode('/', array_map('rawurlencode', explode('/', $decoded))), '/') ?: '/';
        if ($source && self::protectedPath($path)) throw new InvalidArgumentException('Panel, giriş ve işlem adresleri SEO yönlendirmesine kapalıdır.');
        $query = self::query($parts['query'] ?? '');
        return $path.($query !== '' ? '?'.$query : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    private static function query(string $query): string
    {
        parse_str($query, $values);
        ksort($values);
        return http_build_query($values, '', '&', PHP_QUERY_RFC3986);
    }

    public static function activeRules(?int $except = null): array
    {
        return self::compile(SeoRedirect::whereIn('status', ['Aktif', 'active'])
            ->when($except, fn ($q) => $q->whereKeyNot($except))->orderBy('id')->get());
    }

    private static function compile(iterable $records): array
    {
        $rules = [];
        foreach ($records as $rule) {
            try {
                if (!in_array((string) $rule->type, ['301', '302', '410'])) continue;
                $rule->new_url = (string) $rule->type === '410' ? '' : self::targetTemplate($rule->new_url);
            } catch (InvalidArgumentException) {
                continue;
            }
            foreach ($rule->sourceEntries() as $source) {
                try { $source = self::normalizeSource($source); }
                catch (InvalidArgumentException) { continue; }
                $priority = $source['match_type'] === 'exact' ? (str_contains($source['url'], '?') ? 0 : 1) : 2;
                $rules[] = compact('rule', 'source', 'priority') + ['sequence' => count($rules)];
            }
        }
        usort($rules, fn ($a, $b) => [$a['priority'], $a['sequence']] <=> [$b['priority'], $b['sequence']]);
        return $rules;
    }

    private static function regex(string $pattern, bool $ignoreCase): string
    {
        foreach (['~', '#', '%', '!', ';', '@', '`'] as $delimiter) {
            if (!str_contains($pattern, $delimiter)) {
                return $delimiter.'(*LIMIT_MATCH=10000)(*LIMIT_DEPTH=100)(?:'.$pattern.')'.$delimiter.'u'.($ignoreCase ? 'i' : '');
            }
        }
        throw new InvalidArgumentException('Düzenli ifadede çok fazla farklı ayraç var. Deseni sadeleştirin.');
    }

    private static function normalizeSource(mixed $source): array
    {
        if (!is_array($source) || !is_string($source['url'] ?? null) || !isset(self::MATCH_TYPES[$source['match_type'] ?? ''])) {
            throw new InvalidArgumentException('Geçerli bir kaynak adres ve eşleşme türü seçin.');
        }
        $url = trim($source['url']);
        $type = $source['match_type'];
        $ignore = (bool) ($source['ignore_case'] ?? false);
        if ($url === '' || strlen($url) > 1020 || preg_match('/[\x00-\x1f\x7f]/', $url)) {
            throw new InvalidArgumentException('Kaynak adres veya desen boş ya da geçersiz.');
        }
        if ($type === 'exact') {
            $url = self::normalize($url, true);
        } elseif ($type === 'regex') {
            if (@preg_match(self::regex($url, $ignore), '') === false) {
                throw new InvalidArgumentException('Düzenli ifade geçersiz. Örnek: ^/eski/(.*)$');
            }
        } else {
            $url = rawurldecode($url);
            if (preg_match('/[\x00-\x20\x7f\\\\#]/', $url) || str_contains($url, '://') || str_starts_with($url, '//')) {
                throw new InvalidArgumentException('Bu eşleşme için yalnız yol veya yol parçası girin; örnek: /eski/ veya .html');
            }
            if ($type === 'starts_with' && self::protectedPath($url)) {
                throw new InvalidArgumentException('Panel ve işlem adresleri yönlendirmeye kapalıdır.');
            }
        }
        return ['url' => $url, 'match_type' => $type, 'ignore_case' => $ignore];
    }

    private static function targetTemplate(string $target): string
    {
        $tokens = [];
        $target = preg_replace_callback('/\$([1-9][0-9]?)/', function ($match) use (&$tokens) {
            $token = 'seocapture'.bin2hex(random_bytes(8));
            $tokens[$token] = $match[0];
            return $token;
        }, $target);
        return strtr(self::normalize($target), $tokens);
    }

    private static function findMatch(string $url, array $rules): ?array
    {
        $path = rawurldecode(parse_url($url, PHP_URL_PATH) ?? '/');
        foreach ($rules as $entry) {
            $source = $entry['source'];
            $pattern = $source['url'];
            $type = $source['match_type'];
            $captures = [];
            if ($type === 'regex') {
                $matched = @preg_match(self::regex($pattern, $source['ignore_case']), $path, $captures) === 1;
            } else {
                $subject = $type === 'exact' && str_contains($pattern, '?') ? rawurldecode($url) : $path;
                $pattern = $type === 'exact' ? rawurldecode($pattern) : $pattern;
                if ($source['ignore_case']) {
                    $pattern = mb_strtolower($pattern);
                    $subject = mb_strtolower($subject);
                }
                $matched = match ($type) {
                    'exact' => $subject === $pattern,
                    'contains' => str_contains($subject, $pattern),
                    'starts_with' => str_starts_with($subject, $pattern),
                    'ends_with' => str_ends_with($subject, $pattern),
                };
            }
            if ($matched) return [$entry['rule'], $captures];
        }
        return null;
    }

    private static function destination(string $current, string $target): string
    {
        // Keep incoming query parameters for local destinations; explicit target values win.
        if (!str_starts_with($target, '/')) return $target;
        $parts = parse_url($target);
        parse_str(parse_url($current, PHP_URL_QUERY) ?? '', $incoming);
        parse_str($parts['query'] ?? '', $configured);
        $query = http_build_query(array_replace($incoming, $configured), '', '&', PHP_QUERY_RFC3986);
        return $parts['path'].($query !== '' ? '?'.$query : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    /** Return the first redirect only after checking the whole local chain for cycles. */
    public static function resolve(string $url, array $rules): ?array
    {
        $first = null;
        $visited = [];
        for ($hop = 0; $hop < 20; $hop++) {
            $url = explode('#', $url, 2)[0];
            try { $key = self::normalize($url, true); }
            catch (InvalidArgumentException) { return $first; }
            if (isset($visited[$key])) return null;
            $visited[$key] = true;
            $match = self::findMatch($key, $rules);
            if (!$match) return $first;
            [$rule, $captures] = $match;
            if ((string) $rule->type === '410') return $first ?? [$rule, null];
            $target = preg_replace_callback('/\$([1-9][0-9]?)/', fn ($m) => rawurlencode($captures[(int) $m[1]] ?? ''), $rule->new_url);
            try { $target = self::normalize($target); }
            catch (InvalidArgumentException) { return null; }
            $target = self::destination($key, $target);
            $first ??= [$rule, $target];
            if (!str_starts_with($target, '/')) return $first;
            $url = $target;
        }
        return null;
    }

    public static function validate(array $data, ?int $id): array
    {
        $gone = (string) $data['type'] === '410';
        try { $data['new_url'] = $gone ? '' : self::targetTemplate($data['new_url']); }
        catch (InvalidArgumentException $e) { throw ValidationException::withMessages(['new_url' => $e->getMessage()]); }
        $otherSources = [];
        foreach (SeoRedirect::when($id, fn ($q) => $q->whereKeyNot($id))->get() as $record) {
            foreach ($record->sourceEntries() as $source) {
                try { $otherSources[] = self::normalizeSource($source); }
                catch (InvalidArgumentException) { continue; }
            }
        }
        foreach ($data['sources'] as $index => $source) {
            try { $source = self::normalizeSource($source); }
            catch (InvalidArgumentException $e) { throw ValidationException::withMessages(["sources.$index.url" => $e->getMessage()]); }
            foreach ($otherSources as $existing) {
                $fold = $source['ignore_case'] || $existing['ignore_case'];
                if ($existing['match_type'] === $source['match_type'] && ($fold
                    ? mb_strtolower($existing['url']) === mb_strtolower($source['url'])
                    : $existing['url'] === $source['url'])) {
                    throw ValidationException::withMessages(["sources.$index.url" => 'Bu kaynak ve eşleşme için zaten bir kayıt var.']);
                }
            }
            $data['sources'][$index] = $source;
            $otherSources[] = $source;
        }
        if (preg_match('/\$[1-9]/', $data['new_url']) && collect($data['sources'])->contains(fn ($s) => $s['match_type'] !== 'regex')) {
            throw ValidationException::withMessages(['new_url' => '$1 gibi yakalanan grupları kullanmak için bütün kaynaklar Düzenli ifade olmalıdır.']);
        }
        $data['old_url'] = $data['sources'][0]['url'];
        $records = SeoRedirect::whereIn('status', ['Aktif', 'active'])->when($id, fn ($q) => $q->whereKeyNot($id))->orderBy('id')->get();
        $records->push(new SeoRedirect($data));
        $rules = self::compile($records);
        $probes = array_column(array_filter($data['sources'], fn ($s) => $s['match_type'] === 'exact'), 'url');
        if (!$gone && str_starts_with($data['new_url'], '/')) $probes[] = self::normalize($data['new_url']);
        foreach ($probes as $probe) {
            if (self::findMatch($probe, $rules) && !self::resolve($probe, $rules)) {
                throw ValidationException::withMessages(['new_url' => 'Bu hedef kendisine dönen veya geçersiz bir yönlendirme zinciri oluşturuyor.']);
            }
        }
        return $data;
    }
}
