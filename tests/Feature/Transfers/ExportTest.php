<?php

declare(strict_types=1);

use JayI\Roster\Actions\StartExportAction;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Facades\Roster;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Transfers;

function exported(Transfer $transfer): array
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

it('exports only an organization\'s audit entries when scoped', function (): void {
    $other = organization(user(), ['name' => 'Globex']);
    Roster::audit('invoice.paid')->in($this->acme)->record();
    Roster::audit('invoice.paid')->in($other)->record();

    $transfer = app(StartExportAction::class)->execute([
        'type' => 'export_audit',
        'organization' => 'acme',
        'filters' => ['action' => 'invoice.paid'],
    ], $this->owner)->refresh();

    expect($transfer->row_count)->toBe(1)
        ->and($transfer->organization_id)->toBe($this->acme->id);
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
