<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use JayI\Impex\Events\RunFailed;
use JayI\Impex\Models\Run;
use JayI\Impex\Testing\Flows;
use JayI\Roster\Actions\CancelTransferAction;
use JayI\Roster\Actions\ConfirmImportAction;
use JayI\Roster\Actions\StartImportAction;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Team;
use JayI\Roster\Models\Transfer;
use Workbench\App\Models\User;

function startImport(string $type, string $csv, ?User $actor = null, ?string $organization = null): Transfer
{
    return app(StartImportAction::class)->execute(array_filter([
        'type' => $type,
        'organization' => $organization,
        'content' => $csv,
    ]), $actor ?? user());
}

beforeEach(function (): void {
    $this->owner = user(['email' => 'owner@acme.test']);
    $this->acme = organization($this->owner, ['name' => 'Acme', 'domains' => ['acme.test']]);
    Team::factory()->create(['organization_id' => $this->acme->id, 'name' => 'Sales', 'slug' => 'sales']);
});

it('previews a members import without changing anything', function (): void {
    user(['email' => 'linked@acme.test']);

    $transfer = startImport('import_members', implode("\n", [
        'email,name,teams,role',
        'linked@acme.test,Linked,sales,',
        'new@acme.test,New Person,,',
        'friend@example.com,Friend,sales,',
        'not-an-email,Bad,,',
        'owner@acme.test,Owner,,',
    ]), $this->owner, 'acme');

    expect($transfer->status)->toBe(TransferStatus::AwaitingConfirmation)
        ->and($transfer->row_count)->toBe(5)
        ->and(collect($transfer->rows())->pluck('action')->all())->toBe(['link', 'create', 'invite', 'error', 'skip'])
        ->and($transfer->summary())->toMatchArray(['link' => 1, 'create' => 1, 'invite' => 1, 'error' => 1, 'skip' => 1])
        ->and(User::query()->where('email', 'new@acme.test')->exists())->toBeFalse()
        ->and(Membership::query()->count())->toBe(1)
        ->and(Invitation::query()->count())->toBe(0);
});

it('applies a confirmed members import row by row, as the confirmer', function (): void {
    user(['email' => 'linked@acme.test']);

    $transfer = startImport('import_members', implode("\n", [
        'email,name,teams',
        'linked@acme.test,Linked,sales',
        'new@acme.test,New Person,',
        'friend@example.com,Friend,sales',
        'not-an-email,Bad,',
    ]), $this->owner, 'acme');

    $transfer = app(ConfirmImportAction::class)->execute($transfer, $this->owner)->refresh();

    $new = User::query()->where('email', 'new@acme.test')->firstOrFail();

    expect($transfer->status)->toBe(TransferStatus::Completed)
        ->and(collect($transfer->rows())->pluck('result')->all())->toBe(['link', 'create', 'invite', 'error'])
        ->and(Membership::query()->where('organization_id', $this->acme->id)->where('user_id', $new->id)->exists())->toBeTrue()
        ->and(Invitation::query()->where('email', 'friend@example.com')->exists())->toBeTrue()
        ->and(Team::query()->where('slug', 'sales')->firstOrFail()->seats()->count())->toBe(1);

    $entry = AuditEntry::query()->where('action', 'member.added')->where('surface', 'import')->latest('id')->firstOrFail();

    expect((string) $entry->actor_id)->toBe((string) $this->owner->id)
        ->and($entry->context['transfer'])->toBe(['id' => $transfer->id, 'type' => 'import_members']);
});

it('rejects a file with a password column', function (): void {
    $transfer = startImport('import_users', "email,password\na@b.test,secret", $this->owner);

    expect($transfer->status)->toBe(TransferStatus::Failed)
        ->and($transfer->report['error'])->toContain('Passwords cannot be imported');
});

it('creates users and skips existing accounts', function (): void {
    $transfer = startImport('import_users', "email,name,display_name\nada@example.com,Ada Lovelace,Ada\nowner@acme.test,Owner,", $this->owner);
    app(ConfirmImportAction::class)->execute($transfer, $this->owner);

    expect(collect($transfer->refresh()->rows())->pluck('result')->all())->toBe(['create', 'skip'])
        ->and(User::query()->where('email', 'ada@example.com')->firstOrFail()->rosterProfile?->display_name)->toBe('Ada');
});

it('creates and fills teams from existing members', function (): void {
    $transfer = startImport('import_teams', "name,members\nSupport,owner@acme.test\nSales,stranger@example.com", $this->owner, 'acme');

    expect(collect($transfer->rows())->pluck('action')->all())->toBe(['create', 'error']);

    app(ConfirmImportAction::class)->execute($transfer, $this->owner);

    expect(Team::query()->where('slug', 'support')->firstOrFail()->seats()->count())->toBe(1);
});

it('cancels an import awaiting confirmation with nothing applied', function (): void {
    $transfer = startImport('import_users', "email\nada@example.com", $this->owner);

    $transfer = app(CancelTransferAction::class)->execute($transfer);

    expect($transfer->status)->toBe(TransferStatus::Cancelled)
        ->and(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('expires an import nobody confirms', function (): void {
    $transfer = startImport('import_users', "email\nada@example.com", $this->owner);

    $this->travel(25)->hours();
    $this->artisan('impex:tick')->assertSuccessful();

    expect($transfer->refresh()->status)->toBe(TransferStatus::Expired)
        ->and(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('needs an organization for a members import', function (): void {
    startImport('import_members', "email\nada@example.com", $this->owner);
})->throws(ValidationException::class);

it('never applies a row twice when the engine redelivers steps', function (): void {
    $transfer = startImport('import_members', "email,teams\nnew@acme.test,sales\nfriend@example.com,", $this->owner, 'acme');
    app(ConfirmImportAction::class)->execute($transfer, $this->owner);

    Flows::redeliverSteps(Run::query()->findOrFail($transfer->impex_run_id));

    expect(User::query()->where('email', 'new@acme.test')->count())->toBe(1)
        ->and(Invitation::query()->count())->toBe(1)
        ->and(AuditEntry::query()->where('action', 'user.created')->where('surface', 'import')->count())->toBe(1);
});

it('marks the transfer failed when its run fails', function (): void {
    $transfer = startImport('import_users', "email\nada@example.com", $this->owner);

    event(new RunFailed((string) $transfer->impex_run_id));

    expect($transfer->refresh()->status)->toBe(TransferStatus::Failed);
});
