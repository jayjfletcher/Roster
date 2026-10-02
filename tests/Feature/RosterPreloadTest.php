<?php

declare(strict_types=1);

use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Actions\RemoveMemberAction;
use JayI\Roster\Actions\SwitchContextAction;
use JayI\Roster\Models\Profile;
use JayI\Roster\Roster;

it('preloads the same current organization and team as resolving one user at a time', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $ops = app(CreateTeamAction::class)->execute($acme, ['name' => 'Ops']);
    $sales = app(CreateTeamAction::class)->execute($globex, ['name' => 'Sales']);

    $users = [];

    // No memberships.
    $users[] = user();
    // Stored organization and seated team.
    $users[] = $u = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $u->getRouteKey()]);
    app(AddTeamMemberAction::class)->execute($ops, ['user' => $u->getRouteKey()]);
    app(SwitchContextAction::class)->execute($u, ['organization' => 'acme', 'team' => 'ops']);
    // Falls back to the first organization after leaving the stored one.
    $users[] = $u = user();
    app(AddMemberAction::class)->execute($globex, ['user' => $u->getRouteKey()]);
    app(AddMemberAction::class)->execute($acme, ['user' => $u->getRouteKey()]);
    app(AddTeamMemberAction::class)->execute($ops, ['user' => $u->getRouteKey()]);
    app(SwitchContextAction::class)->execute($u, ['organization' => 'acme', 'team' => 'ops']);
    app(RemoveMemberAction::class)->execute($acme, $u);
    // Stored team in another organization than the current one.
    $users[] = $u = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $u->getRouteKey()]);
    app(AddMemberAction::class)->execute($globex, ['user' => $u->getRouteKey()]);
    app(AddTeamMemberAction::class)->execute($sales, ['user' => $u->getRouteKey()]);
    Profile::query()->where('user_id', $u->getKey())->update(['current_organization_id' => $acme->id, 'current_team_id' => $sales->id]);

    $expected = [];

    foreach ($users as $user) {
        app()->forgetScopedInstances();
        $fresh = $user->fresh();
        $expected[] = [app(Roster::class)->organization($fresh)?->slug, app(Roster::class)->team($fresh)?->slug];
    }

    app()->forgetScopedInstances();
    $fresh = array_map(fn ($user) => $user->fresh(['rosterProfile']), $users);
    app(Roster::class)->preload($fresh);

    expect(array_map(fn ($user) => [app(Roster::class)->organization($user)?->slug, app(Roster::class)->team($user)?->slug], $fresh))
        ->toBe($expected)
        ->and($expected)->toBe([[null, null], ['acme', 'ops'], ['globex', null], ['acme', null]]);
});
