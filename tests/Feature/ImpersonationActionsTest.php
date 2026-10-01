<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\EnterImpersonationAction;
use JayI\Roster\Actions\ListImpersonationsAction;
use JayI\Roster\Actions\StartImpersonationAction;
use JayI\Roster\Actions\StopImpersonationAction;
use JayI\Roster\Actions\SuspendUserAction;
use JayI\Roster\Impersonation\StartedImpersonation;
use JayI\Roster\Models\Impersonation;
use JayI\Roster\Models\RoleAssignment;

function startImpersonating($target, $actor, array $data = []): StartedImpersonation
{
    return app(StartImpersonationAction::class)->execute($target, ['reason' => 'Ticket 42'] + $data, $actor);
}

function tokenFrom(string $url): string
{
    return basename((string) parse_url($url, PHP_URL_PATH));
}

it('issues a one-time link and stores only its hash', function (): void {
    $admin = user();
    $ada = user();

    $started = startImpersonating($ada, $admin);

    expect($started->url)->toContain('/roster/impersonate/')->toContain('signature=')
        ->and($started->impersonation->token_hash)->toBe(hash('sha256', tokenFrom($started->url)))
        ->and($started->impersonation->reason)->toBe('Ticket 42')
        ->and($started->impersonation->started_at)->toBeNull();
});

it('refuses yourself, inactive users and missing reasons', function (): void {
    $admin = user();

    expect(fn () => startImpersonating($admin, $admin))->toThrow(ValidationException::class);

    $ada = app(SuspendUserAction::class)->execute(user());

    expect(fn () => startImpersonating($ada, $admin))->toThrow(ValidationException::class)
        ->and(validator(['reason' => ''], StartImpersonationAction::rules())->fails())->toBeTrue();
});

it('refuses super-admins unless you are one', function (): void {
    $admin = user();
    $boss = user();
    grant($boss, 'super-admin');

    expect(fn () => startImpersonating($boss, $admin))->toThrow(ValidationException::class);

    grant($admin, 'super-admin');

    expect(startImpersonating($boss, $admin)->impersonation->exists)->toBeTrue();
});

it('refuses anyone with permissions you lack', function (): void {
    $actor = user();
    RoleAssignment::query()->create(['role_id' => roleWith(['roster.users.impersonate', 'roster.users.view'])->id, 'user_id' => $actor->getKey()]);
    $strong = user();
    RoleAssignment::query()->create(['role_id' => roleWith(['roster.users.delete'])->id, 'user_id' => $strong->getKey()]);
    $weak = user();
    RoleAssignment::query()->create(['role_id' => roleWith(['roster.users.view'])->id, 'user_id' => $weak->getKey()]);

    expect(fn () => startImpersonating($strong, $actor))->toThrow(ValidationException::class)
        ->and(startImpersonating($weak, $actor)->impersonation->exists)->toBeTrue();
});

it('limits organization-scoped impersonation to members', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $admin = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $admin->getRouteKey()]);
    grant($admin, 'admin', $acme);
    $outsider = user();

    expect(fn () => startImpersonating($outsider, $admin, ['organization' => 'acme']))->toThrow(ValidationException::class);

    $member = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $member->getRouteKey()]);

    expect(startImpersonating($member, $admin, ['organization' => 'acme'])->impersonation->organization_id)->toBe($acme->id);
});

it('lets only the impersonator use the link, once, before it expires', function (): void {
    $admin = user();
    $ada = user();
    $started = startImpersonating($ada, $admin);
    $token = tokenFrom($started->url);

    expect(fn () => app(EnterImpersonationAction::class)->execute(['token' => $token], user()))->toThrow(ValidationException::class);

    $impersonation = app(EnterImpersonationAction::class)->execute(['token' => $token], $admin);

    expect($impersonation->isActive())->toBeTrue()
        ->and($impersonation->token_hash)->toBeNull()
        ->and($impersonation->expires_at?->diffInMinutes(now(), true))->toBeGreaterThan(29)
        ->and(fn () => app(EnterImpersonationAction::class)->execute(['token' => $token], $admin))->toThrow(ValidationException::class);

    $late = startImpersonating(user(), $admin);
    $this->travel(6)->minutes();

    expect(fn () => app(EnterImpersonationAction::class)->execute(['token' => tokenFrom($late->url)], $admin))->toThrow(ValidationException::class);
});

it('refuses a link once the user became inactive', function (): void {
    $admin = user();
    $ada = user();
    $started = startImpersonating($ada, $admin);
    app(SuspendUserAction::class)->execute($ada);

    app(EnterImpersonationAction::class)->execute(['token' => tokenFrom($started->url)], $admin);
})->throws(ValidationException::class);

it('stops impersonations idempotently and lists them', function (): void {
    $admin = user();
    $ada = user();
    $started = startImpersonating($ada, $admin);

    $stopped = app(StopImpersonationAction::class)->execute($started->impersonation, ['why' => Impersonation::ENDED_FORCED]);
    $again = app(StopImpersonationAction::class)->execute($stopped, ['why' => Impersonation::ENDED_STOPPED]);

    expect($again->end_reason)->toBe('forced')
        ->and(app(ListImpersonationsAction::class)->execute(['user' => $ada->getRouteKey()])->total())->toBe(1)
        ->and(app(ListImpersonationsAction::class)->execute(['active' => true])->total())->toBe(0);
});
