<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Actions\ListMembersAction;
use JayI\Roster\Actions\RemoveMemberAction;
use JayI\Roster\Actions\SwitchContextAction;
use JayI\Roster\Models\TeamMember;
use JayI\Roster\Roster;

it('adds and lists members', function (): void {
    $organization = organization();
    $ada = user();

    $membership = app(AddMemberAction::class)->execute($organization, ['user' => $ada->getRouteKey()]);

    expect($membership->user?->is($ada))->toBeTrue()
        ->and(app(ListMembersAction::class)->execute($organization)->total())->toBe(2);
});

it('refuses a duplicate member', function (): void {
    $organization = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($organization, ['user' => $ada->getRouteKey()]);

    app(AddMemberAction::class)->execute($organization, ['user' => $ada->getRouteKey()]);
})->throws(ValidationException::class);

it('removes a member with their team seats and context', function (): void {
    $organization = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($organization, ['user' => $ada->getRouteKey()]);
    $team = app(CreateTeamAction::class)->execute($organization, ['name' => 'Ops']);
    app(AddTeamMemberAction::class)->execute($team, ['user' => $ada->getRouteKey()]);
    app(SwitchContextAction::class)->execute($ada, ['organization' => $organization->slug, 'team' => 'ops']);

    app(RemoveMemberAction::class)->execute($organization, $ada);

    expect($organization->membershipFor($ada))->toBeNull()
        ->and(TeamMember::query()->count())->toBe(0)
        ->and(app(Roster::class)->organization($ada))->toBeNull()
        ->and($ada->roster()->refresh()->current_organization_id)->toBeNull();
});

it('refuses to remove the owner or a non-member', function (bool $owner): void {
    $organization = organization();
    $user = $owner ? $organization->owner : user();

    app(RemoveMemberAction::class)->execute($organization, $user);
})->with(['owner' => true, 'stranger' => false])->throws(ValidationException::class);
