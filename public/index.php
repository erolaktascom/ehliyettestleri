<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) === '/install.php') { require __DIR__.'/install.php'; exit; }

// First installation must run before Laravel needs a database, sessions or APP_KEY.
if (!is_file(__DIR__.'/../.env') && !getenv('APP_KEY')) {
    if (is_file(__DIR__.'/../storage/app/installed.json')) { http_response_code(503); exit('Yapılandırma dosyası eksik.'); }
    require __DIR__.'/install.php';
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
