<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Roster\Domains\Impersonation\Http\Controllers\ImpersonationController;

Route::post('users/{user}/impersonate', [ImpersonationController::class, 'store'])->name('users.impersonate');
Route::get('impersonations', [ImpersonationController::class, 'index'])->name('impersonations.index');
Route::delete('impersonations/{impersonation}', [ImpersonationController::class, 'destroy'])->name('impersonations.destroy');
