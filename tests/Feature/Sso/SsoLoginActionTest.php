<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\ApproveUserAction;
use JayI\Roster\Actions\SsoLoginAction;
use JayI\Roster\Actions\SuspendUserAction;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\SsoIdentity;
use JayI\Roster\Sso\IdentityClaims;
use Workbench\App\Models\User;

function claims(string $email = 'ada@acme.test', string $subject = 'sub-1'): IdentityClaims
{
    return new IdentityClaims($subject, $email, 'Ada Lovelace');
}

it('creates an account just in time for the organization\'s domains', function (): void {
    $connection = acmeWithSso();

    [$user, $method] = app(SsoLoginAction::class)->execute($connection, claims());

    expect($method)->toBe('jit')
        ->and($user->getAttribute('email'))->toBe('ada@acme.test')
        ->and($user->getAttribute('email_verified_at'))->not->toBeNull()
        ->and($connection->organization->membershipFor($user))->not->toBeNull()
        ->and(SsoIdentity::query()->sole()->subject)->toBe('sub-1')
        ->and(AuditEntry::query()->where('action', 'sso_login.succeeded')->sole()->context['method'])->toBe('jit');
});

it('matches returning users by subject, even after an email change', function (): void {
    $connection = acmeWithSso();
    [$first] = app(SsoLoginAction::class)->execute($connection, claims());

    [$again, $method] = app(SsoLoginAction::class)->execute($connection, claims('renamed@elsewhere.test'));

    expect($method)->toBe('identity')->and($again->is($first))->toBeTrue();
});

it('links an existing account on a trusted domain', function (): void {
    $connection = acmeWithSso();
    $ada = user(['email' => 'Ada@acme.test']);

    [$user, $method] = app(SsoLoginAction::class)->execute($connection, claims());

    expect($method)->toBe('linked')->and($user->is($ada))->toBeTrue();
});

it('refuses emails outside the organization\'s domains', function (): void {
    $connection = acmeWithSso();
    user(['email' => 'eve@gmail.test']);

    app(SsoLoginAction::class)->execute($connection, claims('eve@gmail.test'));
})->throws(ValidationException::class);

it('records refused sign-ins', function (): void {
    $connection = acmeWithSso();

    try {
        app(SsoLoginAction::class)->execute($connection, claims('eve@gmail.test'));
    } catch (ValidationException) {
    }

    $entry = AuditEntry::query()->where('action', 'sso_login.failed')->sole();

    expect($entry->context['reason'])->toBe('untrusted_email')
        ->and($entry->context['email'])->toBe('eve@gmail.test');
});

it('refuses new accounts when just-in-time is off', function (): void {
    $connection = acmeWithSso(['jit' => false]);

    app(SsoLoginAction::class)->execute($connection, claims());
})->throws(ValidationException::class);

it('refuses inactive users and disabled connections', function (string $case): void {
    $connection = acmeWithSso($case === 'disabled' ? ['enabled' => false] : []);

    if ($case === 'inactive') {
        app(SuspendUserAction::class)->execute(user(['email' => 'ada@acme.test']));
    }

    app(SsoLoginAction::class)->execute($connection, claims());
})->with(['inactive', 'disabled'])->throws(ValidationException::class);

it('creates no duplicate users across logins', function (): void {
    $connection = acmeWithSso();

    app(SsoLoginAction::class)->execute($connection, claims());
    app(SsoLoginAction::class)->execute($connection, claims());

    expect(User::query()->where('email', 'ada@acme.test')->count())->toBe(1);
});

it('keeps a just-in-time account the organization wants approved, and refuses its sign-in until then', function (): void {
    $connection = acmeWithSso();
    $connection->organization->update(['provisioned_status' => 'pending']);

    expect(fn () => app(SsoLoginAction::class)->execute($connection, claims()))
        ->toThrow(ValidationException::class, 'awaiting approval');

    $ada = User::query()->where('email', 'ada@acme.test')->sole();

    expect($ada->rosterStatus()->value)->toBe('pending')
        ->and($connection->organization->membershipFor($ada))->not->toBeNull()
        ->and(SsoIdentity::query()->sole()->user_id)->toBe($ada->id)
        ->and(AuditEntry::query()->where('action', 'sso_login.failed')->sole()->context['reason'])->toBe('pending');

    app(ApproveUserAction::class)->execute($ada);

    [$user, $method] = app(SsoLoginAction::class)->execute($connection, claims());

    expect($method)->toBe('identity')->and($user->is($ada))->toBeTrue();
});
