<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public const SOCIAL_PLATFORMS = ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'x' => 'X (Twitter)'];
    public const SOCIAL_BASES = ['instagram' => 'https://www.instagram.com/', 'tiktok' => 'https://www.tiktok.com/@',
        'youtube' => 'https://www.youtube.com/@', 'facebook' => 'https://www.facebook.com/', 'x' => 'https://x.com/'];
    public const DEFAULT_CONTACT_EMAIL = 'info@ehliyettestleri.com';
    public const DEFAULT_COPYRIGHT = '© {year} {site_name}. Tüm hakları saklıdır.';
    public const DEFAULT_DESCRIPTION = "Türkiye'nin ilk ve lider 3D sinematik MEB e-Sınav simülasyon ve sürücü belgesi hazırlık platformu.";
    protected $fillable = ['site_name', 'contact_email', 'phone', 'address', 'logo', 'favicon', 'site_description', 'copyright_text', 'social_links'];
    protected $casts = ['social_links' => 'array'];
    protected $attributes = ['contact_email' => self::DEFAULT_CONTACT_EMAIL, 'copyright_text' => self::DEFAULT_COPYRIGHT, 'site_description' => self::DEFAULT_DESCRIPTION];

    public function mediaUrl(string $field): ?string
    {
        $path = $this->getAttribute($field);
        if (!$path) return null;
        if (preg_match('~^branding/[a-zA-Z0-9_-]+\.(png|jpe?g|webp|ico)$~', $path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        }
        return NavigationItem::safeUrl($path) ? $path : null;
    }

    public static function safeSocialUrl(string $url): bool
    {
        return NavigationItem::safeUrl($url) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https']);
    }

    public function socialUrl(string $platform): ?string
    {
        if (!isset(self::SOCIAL_BASES[$platform])) return null;
        $value = $this->social_links[$platform] ?? '';
        if (!is_string($value) || $value === '') return null;
        $username = self::socialUsername($platform, $value);
        if ($username !== null) return self::SOCIAL_BASES[$platform].rawurlencode($username);
        // Preserve legacy channel-ID/custom URLs that cannot be converted to a username.
        return self::safeSocialUrl($value) ? $value : null;
    }

    public static function validSocialUsername(string $value): bool
    {
        return (bool) preg_match('/\A[\p{L}\p{N}][\p{L}\p{N}._-]{0,99}\z/u', $value);
    }

    public static function socialUsername(string $platform, string $value): ?string
    {
        if (!isset(self::SOCIAL_BASES[$platform])) return null;
        $value = trim($value);
        if (self::safeSocialUrl($value)) {
            $hosts = ['instagram' => ['instagram.com'], 'tiktok' => ['tiktok.com'], 'youtube' => ['youtube.com'],
                'facebook' => ['facebook.com'], 'x' => ['x.com', 'twitter.com']];
            $host = preg_replace('/^(www\.|m\.)/', '', strtolower(parse_url($value, PHP_URL_HOST) ?? ''));
            if (!in_array($host, $hosts[$platform], true)) return null;
            $path = trim(rawurldecode(parse_url($value, PHP_URL_PATH) ?? ''), '/');
            if ($platform === 'youtube' && !str_starts_with($path, '@')) return null;
            if ($platform === 'facebook' && $path === 'profile.php') return null;
            $value = $path;
        }
        $value = preg_replace('/^@/', '', $value);
        return self::validSocialUsername($value) ? $value : null;
    }

    public function copyrightText(): string
    {
        return strtr($this->copyright_text ?? '', ['{year}' => (string) now()->year, '{site_name}' => $this->site_name]);
    }

}
