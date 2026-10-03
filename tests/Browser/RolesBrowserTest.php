<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Role\Models\RoleModel;

beforeEach(fn () => signInAsSuperAdmin());

it('creates a role and assigns it to a user', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);

    visit('/atrium/roster/roles')
        ->type('name', 'Auditor')
        ->check('#new-role-roster\\.users\\.view')
        ->click('@create-role')
        ->assertSee('Role created.')
        ->assertSee('Auditor');

    $role = RoleModel::query()->where('slug', 'auditor')->sole();

    expect($role->permissions()->pluck('name')->all())->toBe(['roster.users.view']);

    visit('/atrium/roster/users/'.$ada->getRouteKey())
        ->select('role', $role->id)
        ->click('@assign-role')
        ->assertSee('Role assigned.');

    expect(RoleAssignmentModel::query()->where('role_id', $role->id)->where('user_id', $ada->getKey())->exists())->toBeTrue();
});
