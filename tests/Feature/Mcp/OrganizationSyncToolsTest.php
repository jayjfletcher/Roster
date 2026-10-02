<?php

declare(strict_types=1);

use JayI\Roster\Mcp\Tools\LinkOrganizationTool;
use JayI\Roster\Mcp\Tools\ListOrganizationsTool;
use JayI\Roster\Mcp\Tools\SyncOrganizationsTool;
use JayI\Roster\Mcp\Tools\SyncOrganizationTool;
use JayI\Roster\Mcp\Tools\UnlinkOrganizationTool;
use JayI\Roster\Models\AuditEntry;

it('syncs, finds, links and unlinks organizations', function (): void {
    mcpTool(SyncOrganizationTool::class, ['source' => 'erp', 'external_id' => 'C-1', 'name' => 'Initech', 'account_number' => 'A-42'])
        ->assertOk()
        ->assertSee('"outcome":"created"', false);

    mcpTool(SyncOrganizationsTool::class, ['records' => [['source' => 'erp', 'external_id' => 'C-1', 'name' => 'Initech'], ['source' => 'erp', 'external_id' => 'C-2']]])
        ->assertOk()
        ->assertSee('"outcome":"unchanged"', false)
        ->assertSee('"outcome":"error"', false);

    mcpTool(ListOrganizationsTool::class, ['account_number' => 'A-42'])->assertOk()->assertSee('Initech');

    mcpTool(LinkOrganizationTool::class, ['organization' => 'initech', 'source' => 'crm', 'external_id' => '0015g'])->assertOk()->assertSee('0015g');
    mcpTool(UnlinkOrganizationTool::class, ['organization' => 'initech', 'source' => 'crm'])->assertOk()->assertDontSee('0015g');

    expect(AuditEntry::query()->where('action', 'organization.synced')->first()?->surface)->toBe('mcp');
});
