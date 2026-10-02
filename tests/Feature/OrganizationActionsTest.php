<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\CreateOrganizationAction;
use JayI\Roster\Actions\DeleteOrganizationAction;
use JayI\Roster\Actions\ListOrganizationsAction;
use JayI\Roster\Actions\PurgeOrganizationAction;
use JayI\Roster\Actions\RestoreOrganizationAction;
use JayI\Roster\Actions\ShowOrganizationAction;
use JayI\Roster\Actions\TransferOwnershipAction;
use JayI\Roster\Actions\UpdateOrganizationAction;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;

it('creates an organization with its owner as first member', function (): void {
    $owner = user();

    $organization = app(CreateOrganizationAction::class)->execute([
        'name' => 'Acme Inc',
        'owner' => $owner->getRouteKey(),
        'domains' => ['Acme.com'],
        'auto_join' => true,
    ]);

    expect($organization->slug)->toBe('acme-inc')
        ->and($organization->isOwnedBy($owner))->toBeTrue()
        ->and($organization->auto_join)->toBeTrue()
        ->and($organization->domains->pluck('domain')->all())->toBe(['acme.com'])
        ->and($organization->membershipFor($owner)?->source)->toBe(MembershipSource::Direct);
});

it('generates unique slugs', function (): void {
    $first = organization(attributes: ['name' => 'Acme']);
    $second = organization(attributes: ['name' => 'Acme']);

    expect($first->slug)->toBe('acme')->and($second->slug)->toBe('acme-2');
});

it('validates organization input', function (array $data): void {
    organization(attributes: ['name' => 'Taken', 'domains' => ['taken.com']]);

    validator($data + ['name' => 'X', 'owner' => user()->getRouteKey()], CreateOrganizationAction::rules())->validate();
})->with([
    'unknown owner' => [['owner' => 999]],
    'duplicate slug' => [['slug' => 'taken']],
    'domain owned elsewhere' => [['domains' => ['taken.com']]],
    'malformed domain' => [['domains' => ['not a domain']]],
])->throws(ValidationException::class);

it('updates settings and replaces domains', function (): void {
    $organization = organization(attributes: ['domains' => ['old.com']]);

    $updated = app(UpdateOrganizationAction::class)->execute($organization, ['name' => 'Renamed', 'domains' => ['new.com']]);

    expect($updated->name)->toBe('Renamed')
        ->and($updated->domains->pluck('domain')->all())->toBe(['new.com']);

    // Its own slug and domains pass the unique checks.
    expect(validator(['slug' => $organization->slug, 'domains' => ['new.com']], UpdateOrganizationAction::rules($organization))->passes())->toBeTrue();
});

it('lists organizations, filtered by member', function (): void {
    $ada = user();
    organization($ada, ['name' => 'Beta']);
    organization(attributes: ['name' => 'Alpha']);

    $names = fn (array $filters): array => collect(app(ListOrganizationsAction::class)->execute($filters)->items())->pluck('name')->all();

    expect($names([]))->toBe(['Alpha', 'Beta'])
        ->and($names(['user' => $ada->getRouteKey()]))->toBe(['Beta'])
        ->and($names(['search' => 'alp']))->toBe(['Alpha']);
});

it('shows an organization with counts', function (): void {
    $organization = app(ShowOrganizationAction::class)->execute(organization());

    expect($organization->memberships_count)->toBe(1)->and($organization->teams_count)->toBe(0);
});

it('transfers ownership to a member only', function (): void {
    $organization = organization();
    $member = user();

    expect(fn () => app(TransferOwnershipAction::class)->execute($organization, ['user' => $member->getRouteKey()]))
        ->toThrow(ValidationException::class);

    app(AddMemberAction::class)->execute($organization, ['user' => $member->getRouteKey()]);

    expect(app(TransferOwnershipAction::class)->execute($organization, ['user' => $member->getRouteKey()])->isOwnedBy($member))->toBeTrue();
});

it('soft-deletes an organization, keeping everything until it is purged', function (): void {
    $organization = organization();

    app(DeleteOrganizationAction::class)->execute($organization);

    expect(Organization::query()->count())->toBe(0)->and(Membership::query()->count())->toBe(1);

    app(RestoreOrganizationAction::class)->execute(Organization::withTrashed()->sole());
    expect(Organization::query()->count())->toBe(1);

    app(DeleteOrganizationAction::class)->execute(Organization::query()->sole());
    app(PurgeOrganizationAction::class)->execute(Organization::withTrashed()->sole());

    expect(Organization::withTrashed()->count())->toBe(0)->and(Membership::query()->count())->toBe(0);
});

it('refuses to delete or transfer a personal organization', function (string $action): void {
    $organization = app(CreateOrganizationAction::class)->execute(['name' => 'Mine'], user(), personal: true);

    $action === 'delete'
        ? app(DeleteOrganizationAction::class)->execute($organization)
        : app(TransferOwnershipAction::class)->execute($organization, ['user' => user()->getRouteKey()]);
})->with(['delete', 'transfer'])->throws(ValidationException::class);
