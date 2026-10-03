<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Roster\Domains\Transfer\Http\Controllers\TransferFileController;

// Short-lived signed links to finished exports, handed out by the MCP tools.
Route::get('roster/transfer-files/{transfer}', TransferFileController::class)
    ->middleware(['signed', 'throttle:roster'])
    ->name('roster.transfers.file');
