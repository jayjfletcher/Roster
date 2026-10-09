<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\CancelTransferTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\ConfirmImportTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\ListTransfersTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\ShowTransferTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\StartExportTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\StartImportTool;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

beforeEach(function (): void {
    $this->actingAs(user());
});

it('imports from csv text and confirms', function (): void {
    mcpTool(StartImportTool::class, ['type' => 'import_users', 'content' => "email,name\nada@example.com,Ada"])
        ->assertOk()
        ->assertSee('awaiting_confirmation');

    $transfer = TransferModel::query()->sole();

    mcpTool(ShowTransferTool::class, ['transfer' => $transfer->id])->assertOk()->assertSee('"action":"create"', false);
    mcpTool(ConfirmImportTool::class, ['transfer' => $transfer->id])->assertOk()->assertSee('completed');
    mcpTool(ListTransfersTool::class)->assertOk()->assertSee($transfer->id);
});

it('returns a signed download link for a finished export', function (): void {
    mcpTool(StartExportTool::class, ['type' => 'export_users'])->assertOk();

    $transfer = TransferModel::query()->sole();

    mcpTool(ShowTransferTool::class, ['transfer' => $transfer->id])->assertOk()->assertSee('signature=');
});

it('cancels', function (): void {
    mcpTool(StartImportTool::class, ['type' => 'import_users', 'content' => "email\nada@example.com"])->assertOk();

    mcpTool(CancelTransferTool::class, ['transfer' => TransferModel::query()->sole()->id])->assertOk()->assertSee('cancelled');
});
