<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use JayI\Roster\Audit\AuditLog;
use JayI\Roster\Exceptions\AuditLogIsAppendOnlyException;
use JayI\Roster\Facades\Roster;
use JayI\Roster\Models\AuditEntry;

function threeEntries(): void
{
    foreach (['a.one', 'a.two', 'a.three'] as $action) {
        Roster::audit($action)->with(['n' => $action])->record();
    }
}

it('links each entry to the one before', function (): void {
    threeEntries();
    [$first, $second, $third] = AuditEntry::query()->orderBy('id')->get()->all();

    expect($first->previous_hash)->toBeNull()
        ->and($second->previous_hash)->toBe($first->hash)
        ->and($third->previous_hash)->toBe($second->hash)
        ->and(app(AuditLog::class)->verify())->toBeNull();
});

it('finds an entry altered in the database', function (): void {
    threeEntries();
    $second = AuditEntry::query()->orderBy('id')->skip(1)->firstOrFail();

    DB::table('roster_audit_entries')->where('id', $second->id)->update(['context' => json_encode(['n' => 'forged'])]);

    expect(app(AuditLog::class)->verify())->toBe($second->id);
});

it('finds an entry removed from the middle', function (): void {
    threeEntries();
    $ids = AuditEntry::query()->orderBy('id')->pluck('id')->all();

    DB::table('roster_audit_entries')->where('id', $ids[1])->delete();

    expect(app(AuditLog::class)->verify())->toBe($ids[2]);
});

it('refuses to update or delete entries through Eloquent', function (string $operation): void {
    Roster::audit('a.one')->record();
    $entry = AuditEntry::query()->sole();

    $operation === 'update' ? $entry->update(['action' => 'a.two']) : $entry->delete();
})->with(['update', 'delete'])->throws(AuditLogIsAppendOnlyException::class);

it('prunes old entries and verifies from the oldest one left', function (): void {
    $this->travelTo(now()->subDays(400));
    threeEntries();
    $this->travelBack();
    Roster::audit('a.recent')->record();

    $this->artisan('roster:prune-audit')->assertSuccessful();

    expect(AuditEntry::query()->pluck('action')->all())->toBe(['a.recent'])
        ->and(app(AuditLog::class)->verify())->toBeNull();

    $this->artisan('roster:prune-audit', ['--days' => 0])->assertSuccessful();
});

it('keeps everything when retention is unlimited', function (): void {
    config()->set('roster.audit.retention_days', null);
    $this->travelTo(now()->subYears(5));
    Roster::audit('a.old')->record();
    $this->travelBack();

    $this->artisan('roster:prune-audit')->assertSuccessful();

    expect(AuditEntry::query()->count())->toBe(1);
});

it('reports the chain from the command', function (): void {
    threeEntries();

    $this->artisan('roster:verify-audit')->assertSuccessful();

    DB::table('roster_audit_entries')->where('id', AuditEntry::query()->max('id'))->update(['action' => 'forged.entry']);

    $this->artisan('roster:verify-audit')->assertFailed();
});
