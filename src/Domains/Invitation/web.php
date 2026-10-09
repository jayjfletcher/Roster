<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Invitation\Http\Controllers\InvitationWebController;

// The page invitation emails link to. Always on, so links keep working when
// the JSON API is disabled; `roster.invitations.middleware` must authenticate.

/** @var array<int, string> $middleware */
$middleware = config('roster.invitations.middleware', ['web', 'auth']);

Route::middleware($middleware)
    ->prefix('roster/invitation')
    ->name('roster.invitations.')
    ->group(function (): void {
        Route::get('{token}', [InvitationWebController::class, 'show'])->middleware('signed')->name('show');
        Route::post('{token}/accept', [InvitationWebController::class, 'accept'])->name('page.accept');
        Route::post('{token}/decline', [InvitationWebController::class, 'decline'])->name('page.decline');
    });
