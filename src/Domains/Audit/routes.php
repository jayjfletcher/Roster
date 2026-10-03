<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Roster\Domains\Audit\Http\Controllers\AuditController;

Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
Route::post('audit', [AuditController::class, 'store'])->name('audit.store');
Route::get('audit/{entry}', [AuditController::class, 'show'])->whereNumber('entry')->name('audit.show');
