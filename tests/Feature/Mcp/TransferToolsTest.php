<?php

declare(strict_types=1);

use JayI\Roster\Mcp\Tools\CancelTransferTool;
use JayI\Roster\Mcp\Tools\ConfirmImportTool;
use JayI\Roster\Mcp\Tools\ListTransfersTool;
use JayI\Roster\Mcp\Tools\ShowTransferTool;
use JayI\Roster\Mcp\Tools\StartExportTool;
use JayI\Roster\Mcp\Tools\StartImportTool;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\Transfer;

beforeEach(function (): void {
    $this->actingAs(user());
});

it('imports from csv text and confirms', function (): void {
    mcpTool(StartImportTool::class, ['type' => 'import_users', 'content' => "email,name\nada@example.com,Ada"])
        ->assertOk()
        ->assertSee('awaiting_confirmation');

    $transfer = Transfer::query()->sole();

    mcpTool(ShowTransferTool::class, ['transfer' => $transfer->id])->assertOk()->assertSee('"action":"create"', false);
    mcpTool(ConfirmImportTool::class, ['transfer' => $transfer->id])->assertOk()->assertSee('completed');
    mcpTool(ListTransfersTool::class)->assertOk()->assertSee($transfer->id);

    expect(AuditEntry::query()->where('action', 'transfer.confirmed')->sole()->surface)->toBe('mcp');
});

it('returns a signed download link for a finished export', function (): void {
    mcpTool(StartExportTool::class, ['type' => 'export_users'])->assertOk();

    $transfer = Transfer::query()->sole();

    mcpTool(ShowTransferTool::class, ['transfer' => $transfer->id])->assertOk()->assertSee('signature=');
});

it('cancels', function (): void {
    mcpTool(StartImportTool::class, ['type' => 'import_users', 'content' => "email\nada@example.com"])->assertOk();

    mcpTool(CancelTransferTool::class, ['transfer' => Transfer::query()->sole()->id])->assertOk()->assertSee('cancelled');
});
