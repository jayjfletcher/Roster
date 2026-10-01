<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Roster\Http\Web\ImpersonationWebController;
use JayI\Roster\Http\Web\InvitationWebController;
use JayI\Roster\Http\Web\TransferFileController;

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

// Impersonation: the one-time link (signed, and only for the impersonator who
// asked for it) and the way back.
Route::middleware(['web', 'auth'])
    ->prefix('roster/impersonate')
    ->name('roster.impersonation.')
    ->group(function (): void {
        Route::post('leave', [ImpersonationWebController::class, 'leave'])->name('leave');
        Route::get('{token}', [ImpersonationWebController::class, 'enter'])->middleware('signed')->name('enter');
    });

// Short-lived signed links to finished exports, handed out by the MCP tools.
Route::get('roster/transfer-files/{transfer}', TransferFileController::class)
    ->middleware(['signed', 'throttle:roster'])
    ->name('roster.transfers.file');
