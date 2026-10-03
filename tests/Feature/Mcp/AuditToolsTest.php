<?php

declare(strict_types=1);

use JayI\Roster\Domains\Audit\Mcp\Tools\ListAuditEntriesTool;
use JayI\Roster\Domains\Audit\Mcp\Tools\RecordAuditEventTool;
use JayI\Roster\Domains\Audit\Mcp\Tools\ShowAuditEntryTool;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\User\Actions\CreateUserAction;

it('lists, shows and records with parity to the http payload', function (): void {
    app(CreateUserAction::class)->execute(['name' => 'Ada', 'email' => 'ada@example.com']);

    $http = test()->getJson(route('roster.audit.index'))->json();

    mcpTool(ListAuditEntriesTool::class)->assertOk()->assertStructuredContent([
        'data' => $http['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1],
    ]);

    $entry = AuditEntryModel::query()->where('action', 'user.created')->sole();

    mcpTool(ShowAuditEntryTool::class, ['entry' => $entry->id])
        ->assertOk()
        ->assertStructuredContent(test()->getJson(route('roster.audit.show', $entry->id))->json());

    mcpTool(RecordAuditEventTool::class, ['action' => 'invoice.paid', 'subject_label' => 'INV-1'])->assertOk()->assertSee('invoice.paid');

    expect(AuditEntryModel::query()->where('source', 'app')->sole()->surface)->toBe('mcp');
});
