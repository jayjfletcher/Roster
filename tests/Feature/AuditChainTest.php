<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use JayI\Roster\Domains\Audit\Exceptions\AuditLogIsAppendOnlyException;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Audit\Services\AuditLog;
use JayI\Roster\Facades\Roster;

function threeEntries(): void
{
    foreach (['a.one', 'a.two', 'a.three'] as $action) {
        Roster::audit($action)->with(['n' => $action])->record();
    }
}

it('links each entry to the one before', function (): void {
    threeEntries();
    [$first, $second, $third] = AuditEntryModel::query()->orderBy('id')->get()->all();

    expect($first->previous_hash)->toBeNull()
        ->and($second->previous_hash)->toBe($first->hash)
        ->and($third->previous_hash)->toBe($second->hash)
        ->and(app(AuditLog::class)->verify())->toBeNull();
});

it('finds an entry altered in the database', function (): void {
    threeEntries();
    $second = AuditEntryModel::query()->orderBy('id')->skip(1)->firstOrFail();

    DB::table('roster_audit_entries')->where('id', $second->id)->update(['context' => json_encode(['n' => 'forged'])]);

    expect(app(AuditLog::class)->verify())->toBe($second->id);
});

it('finds an entry removed from the middle', function (): void {
    threeEntries();
    $ids = AuditEntryModel::query()->orderBy('id')->pluck('id')->all();

    DB::table('roster_audit_entries')->where('id', $ids[1])->delete();

    expect(app(AuditLog::class)->verify())->toBe($ids[2]);
});

it('refuses to update or delete entries through Eloquent', function (string $operation): void {
    Roster::audit('a.one')->record();
    $entry = AuditEntryModel::query()->sole();

    $operation === 'update' ? $entry->update(['action' => 'a.two']) : $entry->delete();
})->with(['update', 'delete'])->throws(AuditLogIsAppendOnlyException::class);

it('prunes old entries and verifies from the oldest one left', function (): void {
    $this->travelTo(now()->subDays(400));
    threeEntries();
    $this->travelBack();
    Roster::audit('a.recent')->record();

    $this->artisan('roster:prune-audit')->assertSuccessful();

    expect(AuditEntryModel::query()->pluck('action')->all())->toBe(['a.recent'])
        ->and(app(AuditLog::class)->verify())->toBeNull();

    $this->artisan('roster:prune-audit', ['--days' => 0])->assertSuccessful();
});

it('keeps everything when retention is unlimited', function (): void {
    config()->set('roster.audit.retention_days', null);
    $this->travelTo(now()->subYears(5));
    Roster::audit('a.old')->record();
    $this->travelBack();

    $this->artisan('roster:prune-audit')->assertSuccessful();

    expect(AuditEntryModel::query()->count())->toBe(1);
});

it('reports the chain from the command', function (): void {
    threeEntries();

    $this->artisan('roster:verify-audit')->assertSuccessful();

    DB::table('roster_audit_entries')->where('id', AuditEntryModel::query()->max('id'))->update(['action' => 'forged.entry']);

    $this->artisan('roster:verify-audit')->assertFailed();
});

it('chains from the locked head row, which follows every append', function (): void {
    expect(DB::table('roster_audit_chain')->value('head_hash'))->toBeNull();

    $first = Roster::audit('a.one')->record();
    $second = Roster::audit('a.two')->record();

    expect($first->previous_hash)->toBeNull()
        ->and($second->previous_hash)->toBe($first->hash)
        ->and(DB::table('roster_audit_chain')->value('head_hash'))->toBe($second->hash);
});

it('recreates a missing chain head and keeps chaining', function (): void {
    $first = Roster::audit('a.one')->record();
    DB::table('roster_audit_chain')->delete();

    // A lost head restarts the chain; verify treats it as a break.
    $second = Roster::audit('a.two')->record();

    expect($second->previous_hash)->toBeNull()
        ->and(DB::table('roster_audit_chain')->value('head_hash'))->toBe($second->hash)
        ->and(app(AuditLog::class)->verify())->toBe($second->id)
        ->and($first->exists)->toBeTrue();
});

it('prunes in batches', function (): void {
    $this->travelTo(now()->subDays(400));
    foreach (range(1, 1005) as $i) {
        Roster::audit('a.old')->record();
    }
    $this->travelBack();
    Roster::audit('a.new')->record();

    expect(app(AuditLog::class)->prune(365))->toBe(1005)
        ->and(AuditEntryModel::query()->count())->toBe(1);
});
