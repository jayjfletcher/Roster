<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Organization\Actions\AddMemberAction;
use JayI\Roster\Domains\Team\Actions\AddTeamMemberAction;
use JayI\Roster\Domains\Team\Actions\CreateTeamAction;
use JayI\Roster\Domains\Team\Actions\DeleteTeamAction;
use JayI\Roster\Domains\Team\Actions\ListTeamsAction;
use JayI\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use JayI\Roster\Domains\Team\Actions\ShowTeamAction;
use JayI\Roster\Domains\Team\Actions\UpdateTeamAction;
use JayI\Roster\Domains\Team\Models\TeamModel;

it('creates teams with slugs unique within the organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);

    $first = app(CreateTeamAction::class)->execute($acme, ['name' => 'Ops']);
    $second = app(CreateTeamAction::class)->execute($acme, ['name' => 'Ops']);
    $elsewhere = app(CreateTeamAction::class)->execute($globex, ['name' => 'Ops']);

    expect([$first->slug, $second->slug, $elsewhere->slug])->toBe(['ops', 'ops-2', 'ops']);

    expect(validator(['name' => 'X', 'slug' => 'ops'], CreateTeamAction::rules($acme))->fails())->toBeTrue()
        ->and(validator(['name' => 'X', 'slug' => 'ops'], CreateTeamAction::rules(organization()))->passes())->toBeTrue();
});

it('lists, shows, updates and deletes teams', function (): void {
    $organization = organization();
    $team = app(CreateTeamAction::class)->execute($organization, ['name' => 'Ops']);

    expect(app(ListTeamsAction::class)->execute($organization)->total())->toBe(1);

    $team = app(UpdateTeamAction::class)->execute($team, ['name' => 'Operations', 'slug' => 'operations']);

    expect($team->slug)->toBe('operations')
        ->and(app(ShowTeamAction::class)->execute($team)->relationLoaded('memberships'))->toBeTrue();

    app(DeleteTeamAction::class)->execute($team);

    expect(TeamModel::query()->count())->toBe(0);
});

it('seats only organization members, once', function (): void {
    $organization = organization();
    $team = app(CreateTeamAction::class)->execute($organization, ['name' => 'Ops']);
    $ada = user();

    expect(fn () => app(AddTeamMemberAction::class)->execute($team, ['user' => $ada->getRouteKey()]))->toThrow(ValidationException::class);

    app(AddMemberAction::class)->execute($organization, ['user' => $ada->getRouteKey()]);
    $team = app(AddTeamMemberAction::class)->execute($team, ['user' => $ada->getRouteKey()]);

    expect($team->hasMember($ada))->toBeTrue()
        ->and(fn () => app(AddTeamMemberAction::class)->execute($team, ['user' => $ada->getRouteKey()]))->toThrow(ValidationException::class);

    $team = app(RemoveTeamMemberAction::class)->execute($team, $ada);

    expect($team->hasMember($ada))->toBeFalse()
        ->and($organization->membershipFor($ada))->not->toBeNull()
        ->and(fn () => app(RemoveTeamMemberAction::class)->execute($team, $ada))->toThrow(ValidationException::class);
});
