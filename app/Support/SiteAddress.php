<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SiteAddress
{
    public static function normalize(string $url): string
    {
        $url = rtrim(trim($url), '/');
        $parts = parse_url($url);
        if (strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f]/', $url)
            || !filter_var($url, FILTER_VALIDATE_URL) || !$parts
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment']) || !empty($parts['path'])
            || !in_array($parts['scheme'] ?? '', ['https', 'http'], true)
            || ($parts['scheme'] === 'http' && !in_array($parts['host'] ?? '', ['localhost', '127.0.0.1', '[::1]'], true))) {
            throw ValidationException::withMessages(['site_url' => 'Site adresini https://alanadiniz.com şeklinde, alt yol olmadan girin. Yerel adreslerde HTTP kullanılabilir.']);
        }
        return $url;
    }

    public static function save(string $url, Closure $persistSettings): void
    {
        $url = self::normalize($url);
        if ($url === rtrim((string) config('app.url'), '/')) {
            $persistSettings();
            return;
        }

        $lock = @fopen(storage_path('framework/site-address.lock'), 'c');
        if (!$lock) self::fail();
        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                throw ValidationException::withMessages(['site_url' => 'Başka bir adres değişikliği sürüyor. Lütfen yeniden deneyin.']);
            }
            $path = app()->environmentFilePath();
            $before = is_file($path) ? @file_get_contents($path) : false;
            if ($before === false) self::fail();
            $after = $before;
            foreach (['APP_URL' => $url, 'SESSION_SECURE_COOKIE' => str_starts_with($url, 'https://') ? 'true' : 'false'] as $name => $value) {
                $line = $name.'="'.$value.'"';
                $pattern = '/^'.preg_quote($name, '/').'\s*=.*$/m';
                $after = preg_match($pattern, $after)
                    ? preg_replace_callback($pattern, fn () => $line, $after)
                    : rtrim($after).PHP_EOL.$line.PHP_EOL;
            }
            // Drop cached configuration before changing the file; failure must leave all settings intact.
            $cache = app()->getCachedConfigPath();
            if (is_file($cache) && !@unlink($cache)) self::fail();
            self::write($path, $after);
            try {
                $persistSettings();
            } catch (\Throwable $exception) {
                self::write($path, $before);
                throw $exception;
            }
            config(['app.url' => $url, 'session.secure' => str_starts_with($url, 'https://'),
                'filesystems.disks.public.url' => $url.'/storage']);
            Storage::forgetDisk('public');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function write(string $path, string $contents): void
    {
        $temp = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        try {
            if (@file_put_contents($temp, $contents, LOCK_EX) === false) self::fail();
            @chmod($temp, 0600);
            if (!@rename($temp, $path)) self::fail();
        } finally {
            if (is_file($temp)) @unlink($temp);
        }
    }

    private static function fail(): never
    {
        throw ValidationException::withMessages(['site_url' => 'Site adresi kaydedilemedi. Sunucudaki ayar dosyasının ve yapılandırma önbelleğinin yazma izinlerini kontrol edin.']);
    }
}
