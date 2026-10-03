<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Roster\Domains\Sso\Http\Controllers\SsoWebController;

/** @var array<int, string> $middleware */
$middleware = config('roster.sso.middleware', ['web']);

Route::middleware($middleware)
    ->prefix((string) config('roster.sso.prefix', 'roster/sso'))
    ->name('roster.sso.')
    ->group(function (): void {
        Route::get('/', [SsoWebController::class, 'discover'])->name('discover');
        Route::get('{connection}', [SsoWebController::class, 'start'])->name('start');
        // SAML posts the assertion back (ACS); OIDC redirects with a code.
        Route::match(['get', 'post'], '{connection}/callback', [SsoWebController::class, 'callback'])->name('callback');
        Route::get('{connection}/metadata', [SsoWebController::class, 'metadata'])->name('metadata');
        Route::post('{connection}/link', [SsoWebController::class, 'link'])->middleware('auth')->name('link');
    });
