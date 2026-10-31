<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureMasterAdmin;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\SetActiveBranch;
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
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: [
            'app_language',
            'billingpoc_lang',
            'googtrans',
        ]);
        $middleware->alias([
            'active' => EnsureUserActive::class,
            'perm' => CheckPermission::class,
            'active.branch' => SetActiveBranch::class,
            'master.admin' => EnsureMasterAdmin::class,
        ]);
        $middleware->web(append: [
            EnsureUserActive::class,
            SetActiveBranch::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
