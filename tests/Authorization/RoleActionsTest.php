<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Actions\AssignRoleAction;
use JayI\Roster\Actions\CreatePermissionAction;
use JayI\Roster\Actions\CreateRoleAction;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Actions\DeletePermissionAction;
use JayI\Roster\Actions\DeleteRoleAction;
use JayI\Roster\Actions\ListRolesAction;
use JayI\Roster\Actions\ListUserPermissionsAction;
use JayI\Roster\Actions\RevokeRoleAction;
use JayI\Roster\Actions\UpdatePermissionAction;
use JayI\Roster\Actions\UpdateRoleAction;
use JayI\Roster\Models\Permission;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;

it('manages app permissions but keeps built-ins', function (): void {
    $permission = app(CreatePermissionAction::class)->execute(['name' => 'invoices.edit', 'description' => 'Edit invoices']);
    $permission = app(UpdatePermissionAction::class)->execute($permission, ['description' => 'Change invoices']);

    expect($permission->description)->toBe('Change invoices');

    app(DeletePermissionAction::class)->execute($permission);

    expect(fn () => app(DeletePermissionAction::class)->execute(Permission::query()->where('name', 'atrium.view')->sole()))
        ->toThrow(ValidationException::class);
    expect(validator(['name' => 'Not Valid'], CreatePermissionAction::rules())->fails())->toBeTrue();
});

it('creates shared and organization roles', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);

    $shared = app(CreateRoleAction::class)->execute(['name' => 'Billing', 'scope' => 'organization', 'permissions' => ['roster.members.view']]);
    $own = app(CreateRoleAction::class)->execute(['name' => 'Billing', 'scope' => 'organization', 'organization' => 'acme']);

    expect($shared->organization_id)->toBeNull()
        ->and($own->organization_id)->toBe($acme->id)
        ->and($shared->permissions->pluck('name')->all())->toBe(['roster.members.view'])
        ->and(fn () => app(CreateRoleAction::class)->execute(['name' => 'Billing', 'scope' => 'organization']))->toThrow(ValidationException::class)
        ->and(fn () => app(CreateRoleAction::class)->execute(['name' => 'X', 'scope' => 'global', 'organization' => 'acme']))->toThrow(ValidationException::class);

    $slugs = fn (array $filters): array => collect(app(ListRolesAction::class)->execute($filters)->items())->map(fn (Role $role): string => $role->slug.($role->organization_id ? '@own' : ''))->all();

    expect($slugs(['scope' => 'organization']))->not->toContain('billing@own')
        ->and($slugs(['scope' => 'organization', 'organization' => 'acme']))->toContain('billing', 'billing@own');
});

it('stops actors granting permissions they lack', function (): void {
    $actor = user();
    RoleAssignment::query()->create(['role_id' => roleWith(['roster.roles.manage', 'roster.users.view'])->id, 'user_id' => $actor->getKey()]);

    app(CreateRoleAction::class)->execute(['name' => 'Viewer', 'scope' => 'global', 'permissions' => ['roster.users.view']], $actor);

    expect(fn () => app(CreateRoleAction::class)->execute(['name' => 'Deleter', 'scope' => 'global', 'permissions' => ['roster.users.delete']], $actor))
        ->toThrow(ValidationException::class);

    $role = Role::query()->where('slug', 'viewer')->sole();

    expect(fn () => app(UpdateRoleAction::class)->execute($role, ['permissions' => ['roster.users.view', 'roster.users.delete']], $actor))
        ->toThrow(ValidationException::class);

    // Removing permissions is always allowed.
    expect(app(UpdateRoleAction::class)->execute($role, ['permissions' => []], $actor)->permissions)->toBeEmpty();
});

it('assigns roles only in their scope', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    $member = Role::query()->where('slug', 'member')->sole();
    $lead = Role::query()->where('slug', 'lead')->sole();
    $super = Role::query()->where('slug', 'super-admin')->sole();

    $assign = fn (Role $role, array $scope = []) => app(AssignRoleAction::class)->execute($ada, ['role' => $role->id] + $scope);

    expect(fn () => $assign($member))->toThrow(ValidationException::class)
        ->and(fn () => $assign($member, ['organization' => 'acme']))->toThrow(ValidationException::class);

    app(AddMemberAction::class)->execute($acme, ['user' => $ada->getRouteKey()]);
    $ops = app(CreateTeamAction::class)->execute($acme, ['name' => 'Ops']);

    // The default member role is already held.
    expect(fn () => $assign($member, ['organization' => 'acme']))->toThrow(ValidationException::class)
        ->and(fn () => $assign($lead, ['organization' => 'acme', 'team' => 'ops']))->toThrow(ValidationException::class)
        ->and(fn () => $assign($super, ['organization' => 'acme']))->toThrow(ValidationException::class);

    app(AddTeamMemberAction::class)->execute($ops, ['user' => $ada->getRouteKey()]);

    $assignment = $assign($lead, ['organization' => 'acme', 'team' => 'ops']);

    expect($assignment->team_id)->toBe($ops->id);

    $own = app(CreateRoleAction::class)->execute(['name' => 'Own', 'scope' => 'organization', 'organization' => organization(attributes: ['name' => 'Globex'])->slug]);

    expect(fn () => $assign($own, ['organization' => 'acme']))->toThrow(ValidationException::class);
});

it('keeps super roles for super-admins', function (): void {
    $super = Role::query()->where('slug', 'super-admin')->sole();
    $actor = user();
    RoleAssignment::query()->create(['role_id' => roleWith(['roster.roles.assign'])->id, 'user_id' => $actor->getKey()]);
    $ada = user();

    expect(fn () => app(AssignRoleAction::class)->execute($ada, ['role' => $super->id], $actor))->toThrow(ValidationException::class);

    grant($actor, 'super-admin');

    $assignment = app(AssignRoleAction::class)->execute($ada, ['role' => $super->id], $actor);
    $other = user();
    RoleAssignment::query()->create(['role_id' => roleWith(['roster.roles.assign'])->id, 'user_id' => $other->getKey()]);

    expect(fn () => app(RevokeRoleAction::class)->execute($assignment, $other))->toThrow(ValidationException::class);

    app(RevokeRoleAction::class)->execute($assignment, $actor);

    expect(RoleAssignment::query()->whereKey($assignment->id)->exists())->toBeFalse();
});

it('deletes custom roles but not built-ins', function (): void {
    $role = app(CreateRoleAction::class)->execute(['name' => 'Temp', 'scope' => 'global']);

    app(DeleteRoleAction::class)->execute($role);

    expect(fn () => app(DeleteRoleAction::class)->execute(Role::query()->where('slug', 'admin')->sole()))->toThrow(ValidationException::class);
});

it('reports effective permissions', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $ada->getRouteKey()]);

    $result = app(ListUserPermissionsAction::class)->execute($ada, ['organization' => 'acme']);

    expect($result['super_admin'])->toBeFalse()
        ->and($result['permissions'])->toBe(['roster.members.view', 'roster.organizations.view', 'roster.teams.view']);
});
