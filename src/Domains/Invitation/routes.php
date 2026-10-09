<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Invitation\Http\Controllers\InvitationController;

Route::get('organizations/{organization}/invitations', [InvitationController::class, 'index'])->name('organizations.invitations.index');
Route::post('organizations/{organization}/invitations', [InvitationController::class, 'store'])->name('organizations.invitations.store');
Route::delete('organizations/{organization}/invitations/{invitation}', [InvitationController::class, 'revoke'])->name('organizations.invitations.revoke');

Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
Route::post('invitations/{token}/decline', [InvitationController::class, 'decline'])->name('invitations.decline');
