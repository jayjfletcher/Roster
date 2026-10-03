<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Roster\Domains\Impersonation\Http\Controllers\ImpersonationWebController;

// Impersonation: the one-time link (signed, and only for the impersonator who
// asked for it) and the way back.
Route::middleware(['web', 'auth'])
    ->prefix('roster/impersonate')
    ->name('roster.impersonation.')
    ->group(function (): void {
        Route::post('leave', [ImpersonationWebController::class, 'leave'])->name('leave');
        Route::get('{token}', [ImpersonationWebController::class, 'enter'])->middleware('signed')->name('enter');
    });
