<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Team\Http\Controllers\TeamController;
use RefactorCircus\Roster\Domains\Team\Http\Controllers\TeamMemberController;

Route::get('organizations/{organization}/teams', [TeamController::class, 'index'])->name('organizations.teams.index');
Route::post('organizations/{organization}/teams', [TeamController::class, 'store'])->name('organizations.teams.store');
Route::get('organizations/{organization}/teams/{team}', [TeamController::class, 'show'])->name('organizations.teams.show');
Route::patch('organizations/{organization}/teams/{team}', [TeamController::class, 'update'])->name('organizations.teams.update');
Route::delete('organizations/{organization}/teams/{team}', [TeamController::class, 'destroy'])->name('organizations.teams.destroy');
Route::post('organizations/{organization}/teams/{team}/members', [TeamMemberController::class, 'store'])->name('organizations.teams.members.store');
Route::delete('organizations/{organization}/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('organizations.teams.members.destroy');
