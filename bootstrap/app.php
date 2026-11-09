<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['peran' => \App\Http\Middleware\CekPeran::class]);
        // Di Vercel, HTTPS diterima proxy Vercel. Percayai header proxy supaya link CSS/JS memakai https
        // (kalau tidak, browser memblokir CSS http di halaman https → tampilan rusak). Tidak aktif di PC lokal.
        if (getenv('VERCEL')) {
            $middleware->trustProxies(at: '*');
        }
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/admin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
