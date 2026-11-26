<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class SiteVerification
{
    public const PROVIDERS = [
        'google' => ['label' => 'Google Search Console', 'name' => 'google-site-verification'],
        'bing' => ['label' => 'Bing Webmaster Araçları', 'name' => 'msvalidate.01'],
        'yandex' => ['label' => 'Yandex Webmaster', 'name' => 'yandex-verification'],
    ];

    public static function normalize(array $values, string $custom): array
    {
        $tokens = [];
        $tags = [];
        foreach (self::PROVIDERS as $key => $provider) {
            $field = 'verification.'.$key;
            $value = trim($values[$key] ?? '');
            if (str_contains($value, '<')) {
                $parsed = self::parseTags($value, $field);
                if (count($parsed) !== 1 || !isset($parsed[$provider['name']])) self::invalid($field);
                $value = $parsed[$provider['name']];
            }
            if ($value !== '' && !self::validToken($value)) self::invalid($field);
            $tokens[$key] = $value;
            if ($value !== '') $tags[$provider['name']] = $value;
        }
        foreach (self::parseTags($custom, 'custom_verification') as $name => $value) {
            if (in_array($name, array_column(self::PROVIDERS, 'name'), true)) {
                self::invalid('custom_verification', 'Bu servis için yukarıdaki kendi alanını kullanın.');
            }
            $tags[$name] = $value;
        }
        return ['tokens' => $tokens, 'tags' => $tags];
    }

    private static function validToken(string $value): bool
    {
        return strlen($value) <= 2048 && !preg_match('/[\s<>"\x27\x00-\x1F\x7F]/u', $value);
    }

    private static function parseTags(string $html, string $field): array
    {
        if (trim($html) === '') return [];
        preg_match_all('~<meta\b[^<>]*>~i', $html, $matches);
        if (!$matches[0] || count($matches[0]) > 20 || trim(preg_replace('~<meta\b[^<>]*>~i', '', $html)) !== '') self::invalid($field);
        $tags = [];
        foreach ($matches[0] as $tag) {
            $dom = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            try {
                $dom->loadHTML('<!doctype html><html><head>'.$tag.'</head><body></body></html>', LIBXML_NONET);
                $errors = libxml_get_errors();
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $meta = $dom->getElementsByTagName('meta')->item(0);
            if ($errors || !$meta || $meta->attributes->length !== 2 || !$meta->hasAttribute('name') || !$meta->hasAttribute('content')) self::invalid($field);
            $name = strtolower(trim($meta->getAttribute('name')));
            $value = trim($meta->getAttribute('content'));
            if (!preg_match('/\A[a-z][a-z0-9:._-]{0,127}\z/', $name)
                || in_array($name, ['description', 'keywords', 'robots', 'googlebot', 'bingbot', 'viewport', 'referrer', 'csrf-token', 'charset', 'author', 'theme-color'], true)
                || $value === '' || !self::validToken($value) || isset($tags[$name])) self::invalid($field);
            $tags[$name] = $value;
        }
        return $tags;
    }

    private static function invalid(string $field, string $message = 'Geçerli bir doğrulama kimliği veya yalnız name ve content içeren doğrulama meta etiketi girin.'): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public static function customHtml(array $tags): string
    {
        $custom = array_diff_key($tags, array_flip(array_column(self::PROVIDERS, 'name')));
        return implode("\n", array_map(fn ($name, $value) => '<meta name="'.e($name).'" content="'.e($value).'">', array_keys($custom), array_values($custom)));
    }
}