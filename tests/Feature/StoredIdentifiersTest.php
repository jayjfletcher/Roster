<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationDomainModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamMemberModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferRowModel;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Domains\User\Models\UserModel;

it('stores each model under its own class name', function (string $model): void {
    expect((new $model)->getMorphClass())->toBe($model);
})->with([
    ImpersonationModel::class,
    InvitationModel::class,
    MembershipModel::class,
    OrganizationModel::class,
    OrganizationDomainModel::class,
    OrganizationLinkModel::class,
    PermissionModel::class,
    ProfileModel::class,
    RoleModel::class,
    RoleAssignmentModel::class,
    ScimGroupModel::class,
    ScimTokenModel::class,
    ScimUserModel::class,
    SsoConnectionModel::class,
    SsoIdentityModel::class,
    TeamModel::class,
    TeamMemberModel::class,
    TransferModel::class,
    TransferRowModel::class,
    UserModel::class,
]);
