<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Roster\Http\Controllers\AuditController;
use JayI\Roster\Http\Controllers\ImpersonationController;
use JayI\Roster\Http\Controllers\InvitationController;
use JayI\Roster\Http\Controllers\MemberController;
use JayI\Roster\Http\Controllers\OrganizationController;
use JayI\Roster\Http\Controllers\PermissionController;
use JayI\Roster\Http\Controllers\RoleController;
use JayI\Roster\Http\Controllers\ScimTokenController;
use JayI\Roster\Http\Controllers\SsoController;
use JayI\Roster\Http\Controllers\TeamController;
use JayI\Roster\Http\Controllers\TeamMemberController;
use JayI\Roster\Http\Controllers\TransferController;
use JayI\Roster\Http\Controllers\UserContextController;
use JayI\Roster\Http\Controllers\UserController;
use JayI\Roster\Http\Controllers\UserProfileController;
use JayI\Roster\Http\Controllers\UserRoleController;
use JayI\Roster\Http\Controllers\UserStatusController;

// Off unless explicitly enabled: Roster does not authorize these routes
// itself yet, so the configured middleware is the only gate.
if (config('roster.routes.enabled') !== true) {
    return;
}

/** @var array<int, string> $middleware */
$middleware = config('roster.routes.middleware', []);

Route::prefix((string) config('roster.routes.prefix', 'roster'))
    ->middleware($middleware)
    ->name('roster.')
    ->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::patch('users/{user}/profile', [UserProfileController::class, 'update'])->name('users.profile.update');

        Route::post('users/{user}/suspend', [UserStatusController::class, 'suspend'])->name('users.suspend');
        Route::post('users/{user}/deactivate', [UserStatusController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/reactivate', [UserStatusController::class, 'reactivate'])->name('users.reactivate');
        Route::put('users/{user}/context', [UserContextController::class, 'update'])->name('users.context.update');
        Route::post('users/{user}/domain-join', [UserContextController::class, 'domainJoin'])->name('users.domain-join');

        Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
        Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
        Route::patch('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
        Route::delete('organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
        Route::post('organizations/{organization}/transfer', [OrganizationController::class, 'transfer'])->name('organizations.transfer');

        Route::get('organizations/{organization}/members', [MemberController::class, 'index'])->name('organizations.members.index');
        Route::post('organizations/{organization}/members', [MemberController::class, 'store'])->name('organizations.members.store');
        Route::delete('organizations/{organization}/members/{user}', [MemberController::class, 'destroy'])->name('organizations.members.destroy');

        Route::get('organizations/{organization}/teams', [TeamController::class, 'index'])->name('organizations.teams.index');
        Route::post('organizations/{organization}/teams', [TeamController::class, 'store'])->name('organizations.teams.store');
        Route::get('organizations/{organization}/teams/{team}', [TeamController::class, 'show'])->name('organizations.teams.show');
        Route::patch('organizations/{organization}/teams/{team}', [TeamController::class, 'update'])->name('organizations.teams.update');
        Route::delete('organizations/{organization}/teams/{team}', [TeamController::class, 'destroy'])->name('organizations.teams.destroy');
        Route::post('organizations/{organization}/teams/{team}/members', [TeamMemberController::class, 'store'])->name('organizations.teams.members.store');
        Route::delete('organizations/{organization}/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('organizations.teams.members.destroy');

        Route::get('organizations/{organization}/invitations', [InvitationController::class, 'index'])->name('organizations.invitations.index');
        Route::post('organizations/{organization}/invitations', [InvitationController::class, 'store'])->name('organizations.invitations.store');
        Route::delete('organizations/{organization}/invitations/{invitation}', [InvitationController::class, 'revoke'])->name('organizations.invitations.revoke');

        Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
        Route::post('invitations/{token}/decline', [InvitationController::class, 'decline'])->name('invitations.decline');

        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::patch('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('users/{user}/roles', [UserRoleController::class, 'index'])->name('users.roles.index');
        Route::post('users/{user}/roles', [UserRoleController::class, 'store'])->name('users.roles.store');
        Route::delete('users/{user}/roles/{assignment}', [UserRoleController::class, 'destroy'])->name('users.roles.destroy');
        Route::get('users/{user}/permissions', [UserRoleController::class, 'permissions'])->name('users.permissions');

        Route::get('organizations/{organization}/scim-tokens', [ScimTokenController::class, 'index'])->name('organizations.scim-tokens.index');
        Route::post('organizations/{organization}/scim-tokens', [ScimTokenController::class, 'store'])->name('organizations.scim-tokens.store');
        Route::delete('scim-tokens/{token}', [ScimTokenController::class, 'destroy'])->name('scim-tokens.destroy');

        Route::post('users/{user}/impersonate', [ImpersonationController::class, 'store'])->name('users.impersonate');
        Route::get('impersonations', [ImpersonationController::class, 'index'])->name('impersonations.index');
        Route::delete('impersonations/{impersonation}', [ImpersonationController::class, 'destroy'])->name('impersonations.destroy');

        Route::get('organizations/{organization}/sso-connections', [SsoController::class, 'index'])->name('organizations.sso-connections.index');
        Route::post('organizations/{organization}/sso-connections', [SsoController::class, 'store'])->name('organizations.sso-connections.store');
        Route::get('sso-connections/{connection}', [SsoController::class, 'show'])->name('sso-connections.show');
        Route::patch('sso-connections/{connection}', [SsoController::class, 'update'])->name('sso-connections.update');
        Route::delete('sso-connections/{connection}', [SsoController::class, 'destroy'])->name('sso-connections.destroy');
        Route::get('users/{user}/sso-identities', [SsoController::class, 'identities'])->name('users.sso-identities.index');
        Route::delete('sso-identities/{identity}', [SsoController::class, 'unlink'])->name('sso-identities.destroy');

        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
        Route::post('audit', [AuditController::class, 'store'])->name('audit.store');
        Route::get('audit/{entry}', [AuditController::class, 'show'])->whereNumber('entry')->name('audit.show');

        Route::post('imports', [TransferController::class, 'import'])->name('imports.store');
        Route::post('imports/{transfer}/confirm', [TransferController::class, 'confirm'])->name('imports.confirm');
        Route::post('exports', [TransferController::class, 'export'])->name('exports.store');
        Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
        Route::get('transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
        Route::delete('transfers/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');
        Route::get('transfers/{transfer}/download', [TransferController::class, 'download'])->name('transfers.download');
    });
