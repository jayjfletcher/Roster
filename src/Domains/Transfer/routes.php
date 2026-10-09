<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Transfer\Http\Controllers\TransferController;

Route::post('imports', [TransferController::class, 'import'])->name('imports.store');
Route::get('imports/templates/{type}', [TransferController::class, 'template'])->name('imports.templates.show');
Route::post('imports/{transfer}/confirm', [TransferController::class, 'confirm'])->name('imports.confirm');
Route::post('exports', [TransferController::class, 'export'])->name('exports.store');
Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
Route::get('transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
Route::delete('transfers/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');
Route::get('transfers/{transfer}/download', [TransferController::class, 'download'])->name('transfers.download');
