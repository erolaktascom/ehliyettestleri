<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\TrackMemberActivity::class, \App\Http\Middleware\LeaveTestOnNavigation::class]);
        $middleware->append(\App\Http\Middleware\RecordNotFound::class);
        $middleware->append(\App\Http\Middleware\ApplySeoRedirects::class);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            if ($response->getStatusCode() !== 419 || !$request->isMethod('POST')
                || !$request->is('login', 'admin/login')) {
                return $response;
            }

            $adminLogin = $request->is('admin/login');
            $user = $request->user();

            // A form left open in another tab may predate a successful login.
            // Only reuse an already authenticated session; never retry its credentials.
            if ($user && (!$adminLogin || $user->is_admin)) {
                return redirect()->route($user->is_admin ? 'admin.dashboard' : 'dashboard');
            }

            return redirect()->route($adminLogin ? 'admin.login' : 'login')
                ->withErrors(['email' => 'Giriş formunun süresi doldu. Lütfen şifrenizi tekrar girerek giriş yapın.'])
                ->withInput($request->only('email'));
        });
    })->create();
