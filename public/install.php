<?php
// This endpoint runs before Laravel so that missing keys, sessions and database tables cannot break first installation.
ini_set('display_errors', '0');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
if (PHP_VERSION_ID < 80200) { http_response_code(503); exit('Kurulum için PHP 8.2 veya üzeri gerekli.'); }
$root = dirname(__DIR__);
require_once $root.'/app/Support/Installation/InstallState.php';
$state = new \App\Support\Installation\InstallState($root);
if ($state->closed() || getenv('APP_KEY')) { http_response_code(404); echo 'Kurulum kapalı.'; exit; }
$error = ''; $success = false; $old = [];
$checks = $state->requirements();
$checks['Alan adı public dizinine bağlı'] = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') === realpath($root.'/public');
$ready = !in_array(false, $checks, true);
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (!\App\Support\Installation\InstallState::secureRequest($_SERVER)) { $ready = false; $checks['Güvenli HTTPS bağlantısı'] = false; }
if ($ready) {
    session_name('ehg_install');
    session_save_path($root.'/storage/framework/sessions');
    session_start(['cookie_httponly'=>true, 'cookie_secure'=>$secure, 'cookie_samesite'=>'Strict', 'use_strict_mode'=>true]);
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    try {
        $state->assertOpen();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) throw new RuntimeException('Formun süresi doldu. Sayfayı yenileyin.');
            if (time() < ($_SESSION['next_try'] ?? 0)) throw new RuntimeException('Tekrar denemeden önce birkaç saniye bekleyin.');
            $_SESSION['next_try'] = time()+5;
            foreach (['site_name','site_url','admin_name','admin_email','db_host','db_port','db_database','db_username'] as $field) $old[$field] = is_string($_POST[$field] ?? null) ? mb_substr($_POST[$field],0,255) : '';
            $data = \App\Support\Installation\InstallState::validate($_POST);
            require_once $root.'/vendor/autoload.php';
            @set_time_limit(180);
            (new \App\Support\Installation\Installer($state))->install($data);
            $success = true;
            $_SESSION = [];
            session_destroy();
        }
    } catch (Throwable $exception) {
        // Never reflect credentials, SQL, paths or stack traces into the browser.
        $safe = $exception instanceof RuntimeException && get_class($exception) === RuntimeException::class;
        $error = $safe ? $exception->getMessage() : 'Kurulum tamamlanamadı. Sunucu hata kaydını kontrol edip aynı bilgilerle yeniden deneyin.';
        if (!$safe) error_log('Installer failed: '.get_class($exception).' at '.$exception->getFile().':'.$exception->getLine());
        http_response_code(422);
    }
}
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
require $root.'/resources/views/installation/wizard.php';
