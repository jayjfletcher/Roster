<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;

function exported(TransferModel $transfer): array
{
    $csv = app(Transfers::class)->disk()->get((string) $transfer->output_path);

    return array_map(str_getcsv(...), array_values(array_filter(explode("\n", (string) $csv))));
}

beforeEach(function (): void {
    $this->owner = user(['email' => 'owner@acme.test', 'name' => '=HYPERLINK("http://evil.test")']);
    $this->acme = organization($this->owner, ['name' => 'Acme']);
});

it('exports an organization\'s members with formulas neutralized', function (): void {
    $transfer = app(StartExportAction::class)->execute(['type' => 'export_members', 'organization' => 'acme'], $this->owner)->refresh();
    $rows = exported($transfer);

    expect($transfer->status)->toBe(TransferStatus::Completed)
        ->and($transfer->row_count)->toBe(1)
        ->and($rows[0])->toBe(['email', 'name', 'display_name', 'status', 'teams', 'roles', 'owner', 'source', 'joined_at'])
        ->and($rows[1][0])->toBe('owner@acme.test')
        ->and($rows[1][1])->toBe('\'=HYPERLINK("http://evil.test")');
});

it('exports every user', function (): void {
    user(['email' => 'ada@example.com']);

    $transfer = app(StartExportAction::class)->execute(['type' => 'export_users'], $this->owner)->refresh();

    expect($transfer->row_count)->toBe(2)
        ->and(array_column(array_slice(exported($transfer), 1), 1))->toContain('ada@example.com', 'owner@acme.test');
});

it('writes large exports across yields without losing rows', function (): void {
    foreach (range(1, 30) as $i) {
        user(['email' => "user{$i}@example.com"]);
    }

    $transfer = app(StartExportAction::class)->execute(['type' => 'export_users'], $this->owner)->refresh();

    expect($transfer->row_count)->toBe(31)
        ->and(exported($transfer))->toHaveCount(32);
});

it('prunes old transfer files', function (): void {
    $transfer = app(StartExportAction::class)->execute(['type' => 'export_users'], $this->owner)->refresh();
    $path = (string) $transfer->output_path;

    $this->travel(8)->days();
    $this->artisan('roster:prune-transfers')->assertSuccessful();

    expect(app(Transfers::class)->disk()->exists($path))->toBeFalse()
        ->and($transfer->refresh()->output_path)->toBeNull();
});

it('exports organizations in the import columns, one row per external record', function (): void {
    $sync = app(SyncOrganizationAction::class);
    $sync->execute(['source' => 'erp', 'external_id' => 'C-1', 'name' => 'Initech', 'account_number' => 'A-1', 'domains' => ['initech.test', 'initech.example']]);
    $sync->execute(['source' => 'crm', 'external_id' => '0015g', 'organization' => 'initech']);

    $transfer = app(StartExportAction::class)->execute(['type' => 'export_organizations'], $this->owner)->refresh();
    $rows = exported($transfer);

    expect($rows[0])->toBe(['source', 'external_id', 'name', 'account_number', 'slug', 'domains', 'owner'])
        ->and(array_slice($rows, 1))->toContain(
            ['', '', 'Acme', '', 'acme', '', 'owner@acme.test'],
            ['crm', '0015g', 'Initech', '', 'initech', 'initech.example;initech.test', ''],
            ['erp', 'C-1', 'Initech', 'A-1', 'initech', 'initech.example;initech.test', ''],
        );

    $erp = app(StartExportAction::class)->execute(['type' => 'export_organizations', 'filters' => ['external_source' => 'erp']], $this->owner)->refresh();

    expect(array_slice(exported($erp), 1))->toBe([['erp', 'C-1', 'Initech', 'A-1', 'initech', 'initech.example;initech.test', '']]);
});

it('round-trips an organizations export through the import unchanged', function (): void {
    app(SyncOrganizationAction::class)->execute(['source' => 'erp', 'external_id' => 'C-1', 'name' => 'Initech', 'account_number' => 'A-1', 'domains' => ['initech.test']]);

    $export = app(StartExportAction::class)->execute(['type' => 'export_organizations', 'filters' => ['external_source' => 'erp']], $this->owner)->refresh();

    $import = app(StartImportAction::class)->execute([
        'type' => 'import_organizations',
        'content' => app(Transfers::class)->disk()->get((string) $export->output_path),
    ], $this->owner);

    expect(collect($import->rows())->pluck('action')->all())->toBe(['skip']);
});
