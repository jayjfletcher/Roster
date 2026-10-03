<?php

declare(strict_types=1);

use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Transfer\Mcp\Tools\CancelTransferTool;
use JayI\Roster\Domains\Transfer\Mcp\Tools\ConfirmImportTool;
use JayI\Roster\Domains\Transfer\Mcp\Tools\ListTransfersTool;
use JayI\Roster\Domains\Transfer\Mcp\Tools\ShowTransferTool;
use JayI\Roster\Domains\Transfer\Mcp\Tools\StartExportTool;
use JayI\Roster\Domains\Transfer\Mcp\Tools\StartImportTool;
use JayI\Roster\Domains\Transfer\Models\TransferModel;

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

    expect(AuditEntryModel::query()->where('action', 'transfer.confirmed')->sole()->surface)->toBe('mcp');
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
