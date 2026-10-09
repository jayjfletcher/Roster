<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

it('grants super-admin by email', function (): void {
    $ada = user(['email' => 'ada@example.com']);

    $this->artisan('roster:grant-super-admin', ['email' => 'ADA@example.com'])->assertSuccessful();

    expect(app(Permissions::class)->isSuperAdmin($ada))->toBeTrue();

    // Idempotent.
    $this->artisan('roster:grant-super-admin', ['email' => 'ada@example.com'])->assertSuccessful();
});

it('fails for an unknown email', function (): void {
    $this->artisan('roster:grant-super-admin', ['email' => 'nobody@example.com'])->assertFailed();
});

it('restores missing built-ins without touching edits', function (): void {
    PermissionModel::query()->where('name', 'atrium.view')->delete();
    $member = RoleModel::query()->where('slug', 'member')->sole();
    $member->permissions()->detach();

    $this->artisan('roster:sync-permissions')->assertSuccessful();

    expect(PermissionModel::query()->where('name', 'atrium.view')->exists())->toBeTrue()
        // Admin edits to existing roles are kept.
        ->and($member->permissions()->count())->toBe(0);
});
