<?php
namespace App\Support\Installation;

use RuntimeException;

class InstallState
{
    public function __construct(public readonly string $root) {}

    public function path(string $name): string { return $this->root.'/storage/app/'.$name; }

    public function closed(): bool
    {
        return is_file($this->root.'/.env') || is_file($this->path('installed.json'))
            || is_file($this->root.'/bootstrap/cache/config.php');
    }

    public function requirements(): array
    {
        $checks = ['PHP 8.2 veya üzeri' => PHP_VERSION_ID >= 80200, 'Hazır uygulama dosyaları' => is_file($this->root.'/vendor/autoload.php')];
        $checks['Derlenmiş tasarım dosyaları'] = is_file($this->root.'/public/build/manifest.json');
        foreach (['pdo_mysql','mbstring','openssl','fileinfo','dom','xml','ctype','tokenizer','session','intl'] as $ext) $checks['PHP '.$ext] = extension_loaded($ext);
        foreach (['', '/storage', '/storage/app', '/storage/app/public', '/storage/framework/sessions', '/storage/framework/views', '/storage/framework/cache', '/storage/logs', '/bootstrap/cache', '/public'] as $dir) {
            $checks[($dir ?: 'Proje kökü').' yazılabilir'] = is_dir($this->root.$dir) && is_writable($this->root.$dir);
        }
        return $checks;
    }

    public function assertOpen(): void
    {
        if ($this->closed()) throw new RuntimeException('Kurulum kapalı.');
    }

    public function write(string $path, string $value): void
    {
        $temp = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        if (file_put_contents($temp, $value, LOCK_EX) === false) throw new RuntimeException('Dosya yazılamadı.');
        chmod($temp, 0600);
        if (!rename($temp, $path)) { @unlink($temp); throw new RuntimeException('Dosya kaydedilemedi.'); }
    }

    public static function secureRequest(array $server): bool
    {
        if (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') return true;
        $host = parse_url('http://'.($server['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        return in_array($server['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)
            && in_array($host, ['localhost','127.0.0.1','[::1]'], true);
    }
    public static function validate(array $data): array
    {
        foreach (['site_name','site_url','admin_name','admin_email','password','password_confirmation'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || preg_match('/[\x00-\x1f\x7f]/', $data[$field])) throw new RuntimeException('Tüm alanları geçerli metinlerle doldurun.');
        }
        foreach (['db_host', 'db_port', 'db_database', 'db_username', 'db_password'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || strlen($data[$field]) > 512
                || preg_match('/[\x00-\x1f\x7f]/', $data[$field])) throw new RuntimeException('Veritabanı bilgilerini geçerli metinlerle doldurun.');
        }
        foreach (['db_host', 'db_port', 'db_database', 'db_username'] as $field) $data[$field] = trim($data[$field]);
        if (!preg_match('/\A[a-zA-Z0-9._:-]{1,253}\z/', $data['db_host'])) throw new RuntimeException('Geçerli bir veritabanı sunucusu girin. Portu ayrı alana yazın.');
        if (!ctype_digit($data['db_port']) || (int)$data['db_port'] < 1 || (int)$data['db_port'] > 65535) throw new RuntimeException('Veritabanı portu 1–65535 arasında olmalı.');
        if (!preg_match('/\A[a-zA-Z0-9_$-]{1,64}\z/', $data['db_database'])
            || in_array(strtolower($data['db_database']), ['mysql','sys','information_schema','performance_schema'], true)) throw new RuntimeException('Geçerli bir site veritabanı adı girin.');
        if ($data['db_username'] === '' || strlen($data['db_username']) > 128) throw new RuntimeException('Veritabanı kullanıcı adını girin.');
        foreach (['site_name','admin_name'] as $field) {
            if (mb_strlen(trim($data[$field])) < 2 || mb_strlen($data[$field]) > 100) throw new RuntimeException('Site ve yönetici adı 2–100 karakter olmalı.');
            $data[$field] = trim($data[$field]);
        }
        if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL) || strlen($data['admin_email']) > 254) throw new RuntimeException('Geçerli bir yönetici e-posta adresi girin.');
        if (mb_strlen($data['password']) < 12 || strlen($data['password']) > 72 || $data['password'] !== $data['password_confirmation']) throw new RuntimeException('Şifre en az 12 karakter, en fazla 72 bayt olmalı ve iki alanda aynı olmalı.');
        $url = rtrim(trim($data['site_url']), '/');
        $parts = parse_url($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !$parts || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || !empty($parts['path']) || !in_array($parts['scheme'] ?? '', ['https','http'])
            || ($parts['scheme'] !== 'https' && !in_array($parts['host'], ['localhost','127.0.0.1','[::1]']))) {
            throw new RuntimeException('Site adresini https://alanadiniz.com şeklinde, alt klasör olmadan girin. Yerel kurulumda HTTP kullanılabilir.');
        }
        $data['site_url'] = $url;
        return $data;
    }

    public static function environment(array $data, string $key): string
    {
        $values = ['APP_NAME'=>$data['site_name'], 'APP_ENV'=>'production', 'APP_KEY'=>$key, 'APP_DEBUG'=>'false',
            'APP_URL'=>$data['site_url'], 'APP_LOCALE'=>'tr', 'APP_FALLBACK_LOCALE'=>'tr',
            'DB_CONNECTION'=>'mysql', 'DB_HOST'=>$data['db_host'], 'DB_PORT'=>$data['db_port'],
            'DB_DATABASE'=>$data['db_database'], 'DB_USERNAME'=>$data['db_username'], 'DB_PASSWORD'=>$data['db_password'],
            'DB_CHARSET'=>'utf8mb4', 'DB_COLLATION'=>'utf8mb4_unicode_ci', 'SESSION_DRIVER'=>'file', 'SESSION_SECURE_COOKIE'=>str_starts_with($data['site_url'], 'https://') ? 'true' : 'false',
            'CACHE_STORE'=>'file', 'QUEUE_CONNECTION'=>'database', 'MAIL_MAILER'=>'log', 'LOG_CHANNEL'=>'single', 'LOG_LEVEL'=>'error'];
        $env = '';
        foreach ($values as $name=>$value) {
            $quoted = str_replace(['\\','"','$'], ['\\\\','\\"','\\$'], $value);
            $env .= $name.'="'.$quoted.'"'.PHP_EOL;
        }
        return $env;
    }
}
