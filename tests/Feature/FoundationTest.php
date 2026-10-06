<?php

declare(strict_types=1);

use JayI\Foundation\Packages\Package;
use JayI\Foundation\Packages\PackageRegistry;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Mcp\RosterServer;
use JayI\Roster\Mcp\Tools\ListRosterHistoryTool;
use JayI\Roster\RosterServiceProvider;
use JayI\Roster\Tests\Fixtures\Billing\InvoicePaidActionEvent;
use JayI\Roster\Tests\Fixtures\Billing\InvoicePayingActionEvent;

it('registers Roster with jayi/foundation', function (): void {
    $package = app(PackageRegistry::class)->get('roster');

    expect($package->label)->toBe('Roster')
        ->and($package->server)->toBe(RosterServer::class)
        ->and($package->authorization)->toBeTrue()
        ->and(app(PackageRegistry::class)->for(RosterServiceProvider::class))->toBe($package);
});

it('answers the history route with 404 while no shared audit log is installed', function (): void {
    $this->getJson(route('roster.history.index'))
        ->assertNotFound()
        ->assertJsonPath('message', 'No audit log is installed. Install jayi/keen to record history.');
});

it('lists the history tool on the MCP server', function (): void {
    expect(RosterServer::TOOLS)->toContain(ListRosterHistoryTool::class)
        ->and(app(ListRosterHistoryTool::class)->name())->toBe('list-roster-history-tool');

    mcpTool(ListRosterHistoryTool::class)->assertHasErrors(['No audit log is installed']);
});

it('records only Roster action events, not those of other packages', function (): void {
    app(PackageRegistry::class)->register(Package::make('billing', 'JayI\Roster\Tests\Fixtures\Billing'));
    $ada = user(['name' => 'Ada']);
    $before = AuditEntryModel::query()->count();

    event(new InvoicePayingActionEvent($ada));
    event(new InvoicePaidActionEvent($ada));

    expect(AuditEntryModel::query()->count())->toBe($before);
});
