<?php

declare(strict_types=1);

use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Models\Permission;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;

beforeEach(function (): void {
    $this->actingAs(user(['name' => 'Admin']));
});

it('manages permissions', function (): void {
    $this->get(route('atrium.roster.permissions.index'))->assertOk()->assertSee('roster.users.view');

    $this->post(route('atrium.roster.permissions.store'), ['name' => 'invoices.edit', 'description' => 'Edit'])->assertRedirect();
    $this->patch(route('atrium.roster.permissions.update', 'invoices.edit'), ['description' => 'Change'])->assertRedirect();

    expect(Permission::query()->where('name', 'invoices.edit')->sole()->description)->toBe('Change');

    $this->delete(route('atrium.roster.permissions.destroy', 'invoices.edit'))->assertRedirect();

    expect(Permission::query()->where('name', 'invoices.edit')->exists())->toBeFalse();
});

it('manages roles', function (): void {
    $this->get(route('atrium.roster.roles.index'))->assertOk()->assertSee('Super admin');

    $this->post(route('atrium.roster.roles.store'), ['name' => 'Auditor', 'scope' => 'global', 'permissions' => ['roster.users.view']])->assertRedirect();
    $role = Role::query()->where('slug', 'auditor')->sole();

    $this->get(route('atrium.roster.roles.show', $role->id))->assertOk()->assertSee('Auditor');

    $this->patch(route('atrium.roster.roles.update', $role->id), ['name' => 'Auditor'])->assertRedirect();

    // Unchecking every box clears the permissions.
    expect($role->permissions()->count())->toBe(0);

    $this->delete(route('atrium.roster.roles.destroy', $role->id))->assertRedirect(route('atrium.roster.roles.index'));
});

it('assigns and revokes from the user page', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $ada->getRouteKey()]);
    $admin = Role::query()->where('slug', 'admin')->sole();

    $this->get(route('atrium.roster.users.show', $ada->getRouteKey()))->assertOk()->assertSee('Member');

    $this->post(route('atrium.roster.users.roles.store', $ada->getRouteKey()), ['role' => $admin->id, 'organization' => 'acme', 'team' => ''])->assertRedirect();

    $assignment = RoleAssignment::query()->where('role_id', $admin->id)->sole();

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'roles']))->assertOk()->assertSee('Admin');

    $this->delete(route('atrium.roster.users.roles.destroy', [$ada->getRouteKey(), $assignment->id]))->assertRedirect();

    expect(RoleAssignment::query()->whereKey($assignment->id)->exists())->toBeFalse();
});
