<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use Illuminate\Http\Request;
use RefactorCircus\Roster\Atrium\RosterPlugin;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;

/**
 * Atrium screens: [method, name, parameters].
 *
 * @return array<string, array{0: string, 1: string, 2: Closure(array<string, mixed>): array<int, mixed>}>
 */
function screens(): array
{
    $user = fn (array $w): array => [$w['target']->getRouteKey()];

    return [
        'users.index' => ['GET', 'atrium.roster.users.index', fn (): array => []],
        'users.create' => ['GET', 'atrium.roster.users.create', fn (): array => []],
        'users.show' => ['GET', 'atrium.roster.users.show', $user],
        'users.suspend' => ['POST', 'atrium.roster.users.suspend', $user],
        'users.status' => ['POST', 'atrium.roster.users.status', $user],
        'users.destroy' => ['DELETE', 'atrium.roster.users.destroy', $user],
        'users.restore' => ['POST', 'atrium.roster.users.restore', $user],
        'users.purge' => ['DELETE', 'atrium.roster.users.purge', $user],
        'users.approve' => ['POST', 'atrium.roster.users.approve', $user],
        'users.reject' => ['POST', 'atrium.roster.users.reject', $user],
        'organizations.index' => ['GET', 'atrium.roster.organizations.index', fn (): array => []],
        'organizations.show' => ['GET', 'atrium.roster.organizations.show', fn (): array => ['acme']],
        'organizations.destroy' => ['DELETE', 'atrium.roster.organizations.destroy', fn (): array => ['acme']],
        'organizations.restore' => ['POST', 'atrium.roster.organizations.restore', fn (): array => ['acme']],
        'organizations.purge' => ['DELETE', 'atrium.roster.organizations.purge', fn (): array => ['acme']],
        'organizations.members.store' => ['POST', 'atrium.roster.organizations.members.store', fn (): array => ['acme']],
        'organizations.links.store' => ['POST', 'atrium.roster.organizations.links.store', fn (): array => ['acme']],
        'organizations.links.destroy' => ['DELETE', 'atrium.roster.organizations.links.destroy', fn (): array => ['acme', 'erp']],
        'teams.show' => ['GET', 'atrium.roster.teams.show', fn (): array => ['acme', 'ops']],
        'teams.destroy' => ['DELETE', 'atrium.roster.teams.destroy', fn (): array => ['acme', 'ops']],
        'invitations.store' => ['POST', 'atrium.roster.invitations.store', fn (): array => ['acme']],
        'roles.index' => ['GET', 'atrium.roster.roles.index', fn (): array => []],
        'roles.show' => ['GET', 'atrium.roster.roles.show', fn (array $w): array => [$w['role']->id]],
        'roles.store' => ['POST', 'atrium.roster.roles.store', fn (): array => []],
        'users.roles.store' => ['POST', 'atrium.roster.users.roles.store', $user],
        'permissions.index' => ['GET', 'atrium.roster.permissions.index', fn (): array => []],
        'permissions.store' => ['POST', 'atrium.roster.permissions.store', fn (): array => []],
        'transfers.index' => ['GET', 'atrium.roster.transfers.index', fn (): array => ['organization' => 'acme']],
        'transfers.import' => ['POST', 'atrium.roster.transfers.import', fn (): array => ['type' => 'import_users']],
        'transfers.export' => ['POST', 'atrium.roster.transfers.export', fn (): array => ['type' => 'export_users']],
        'transfers.show' => ['GET', 'atrium.roster.transfers.show', fn (array $w): array => [$w['transfer']->id]],
        'transfers.confirm' => ['POST', 'atrium.roster.transfers.confirm', fn (array $w): array => [$w['transfer']->id]],
        'transfers.cancel' => ['DELETE', 'atrium.roster.transfers.cancel', fn (array $w): array => [$w['transfer']->id]],
        'transfers.download' => ['GET', 'atrium.roster.transfers.download', fn (array $w): array => [$w['transfer']->id]],
        'transfers.template' => ['GET', 'atrium.roster.transfers.template', fn (): array => ['import_users']],
    ];
}

it('keeps atrium closed without atrium.view', function (): void {
    $this->actingAs(user())->get(route('atrium.roster.users.index'))->assertForbidden();
});

it('forbids each screen to an atrium user without the permission', function (string $method, string $name, Closure $parameters): void {
    $world = authorizationWorld();
    $viewer = user();
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['atrium.view'])->id, 'user_id' => $viewer->getKey()]);

    $this->actingAs($viewer)->call($method, route($name, $parameters($world)))->assertForbidden();
})->with(fn (): array => array_diff_key(screens(), array_flip(OPEN_TO_SIGNED_IN)));

it('opens each screen to a super-admin', function (string $method, string $name, Closure $parameters): void {
    $world = authorizationWorld();
    $admin = user();
    grant($admin, 'super-admin');

    expect($this->actingAs($admin)->call($method, route($name, $parameters($world)))->status())->not->toBeIn([401, 403]);
})->with(screens());

it('hides navigation the user cannot use', function (): void {
    $viewer = user();
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['atrium.view', 'roster.users.view'])->id, 'user_id' => $viewer->getKey()]);

    $request = Request::create('/');
    $request->setUserResolver(fn () => $viewer);

    $visible = collect(app(RosterPlugin::class)->navigation())
        ->filter(fn ($item): bool => $item->isAuthorized($request))
        ->map(fn ($item): string => $item->label)
        ->values()
        ->all();

    // roster.users.view also allows exporting users.
    expect($visible)->toBe(['Users', 'Imports & exports']);
});
