<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\CreateUserAction;
use JayI\Roster\Actions\RecordAuditEventAction;
use JayI\Roster\Audit\AuditLog;
use JayI\Roster\Facades\Roster;
use JayI\Roster\Models\AuditEntry;

it('records app events with a model subject through the fluent API', function (): void {
    $ada = user(['name' => 'Ada']);
    $acme = organization(attributes: ['name' => 'Acme']);
    $this->actingAs($ada);

    $entry = Roster::audit('invoice.paid')
        ->on($acme)
        ->in($acme)
        ->with(['amount' => 100, 'card_token' => 'tok_123'])
        ->changes(['status' => ['open', 'paid']])
        ->record();

    expect($entry->source)->toBe('app')
        ->and($entry->action)->toBe('invoice.paid')
        ->and((string) $entry->actor_id)->toBe((string) $ada->getKey())
        ->and($entry->subject_label)->toBe('Acme')
        ->and($entry->organization_id)->toBe($acme->id)
        ->and($entry->changes)->toBe(['status' => ['open', 'paid']])
        ->and($entry->context['amount'])->toBe(100);
});

it('redacts secrets in app events', function (): void {
    config()->set('roster.audit.redact', ['card_token']);

    $entry = Roster::audit('card.updated')->with(['card_token' => 'tok_123', 'nested' => ['password' => 'x']])->changes(['password' => ['a', 'b']])->record();

    expect($entry->context)->toBe(['card_token' => '[redacted]', 'nested' => ['password' => '[redacted]']])
        ->and($entry->changes)->toBe(['password' => ['[redacted]', '[redacted]']]);
});

it('records app events with a free subject through the Action', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);

    $entry = app(RecordAuditEventAction::class)->execute([
        'action' => 'contract.signed',
        'subject_type' => 'contract',
        'subject_id' => 'C-42',
        'subject_label' => 'Master services agreement',
        'organization' => 'acme',
    ]);

    expect($entry->subject_type)->toBe('contract')
        ->and($entry->subject_id)->toBe('C-42')
        ->and($entry->organization_id)->toBe($acme->id)
        ->and($entry->actor_id)->toBeNull();
});

it('validates action names', function (string $action): void {
    Roster::audit($action)->record();
})->with(['Invoice Paid', 'invoice', 'invoice.', '.paid'])->throws(ValidationException::class);

it('never lets the API record roster entries', function (): void {
    $this->postJson(route('roster.audit.store'), ['action' => 'user.deleted', 'source' => 'roster'])
        ->assertCreated()
        ->assertJsonPath('data.source', 'app');
});

it('chains app events with roster entries', function (): void {
    $create = fn (string $email) => app(CreateUserAction::class)->execute(['name' => 'N', 'email' => $email]);

    $create('a@example.com');
    Roster::audit('invoice.paid')->record();
    $create('b@example.com');

    expect(app(AuditLog::class)->verify())->toBeNull()
        ->and(AuditEntry::query()->pluck('source')->unique()->sort()->values()->all())->toBe(['app', 'roster']);
});

it('filters by source', function (): void {
    app(CreateUserAction::class)->execute(['name' => 'N', 'email' => 'n@example.com']);
    Roster::audit('invoice.paid')->record();

    $this->getJson(route('roster.audit.index', ['source' => 'app']))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.action', 'invoice.paid');
});
