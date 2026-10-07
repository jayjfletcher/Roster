<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use JayI\Roster\Domains\Impersonation\Models\ImpersonationModel;
use JayI\Roster\Domains\Invitation\Models\InvitationModel;
use JayI\Roster\Domains\Organization\Models\MembershipModel;
use JayI\Roster\Domains\Organization\Models\OrganizationDomainModel;
use JayI\Roster\Domains\Organization\Models\OrganizationLinkModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Permission\Models\PermissionModel;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Role\Models\RoleModel;
use JayI\Roster\Domains\Scim\Models\ScimGroupModel;
use JayI\Roster\Domains\Scim\Models\ScimTokenModel;
use JayI\Roster\Domains\Scim\Models\ScimUserModel;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;
use JayI\Roster\Domains\Sso\Models\SsoIdentityModel;
use JayI\Roster\Domains\Team\Models\TeamMemberModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Domains\Transfer\Models\TransferRowModel;
use JayI\Roster\Domains\User\Models\ProfileModel;
use JayI\Roster\Domains\User\Models\UserModel;

/**
 * The class names Roster's models were stored under before they moved into
 * their domains, and the models they are now.
 *
 * @return array<string, array{0: string, 1: class-string}>
 */
function rosterMorphAliases(): array
{
    $models = [
        'Impersonation' => ImpersonationModel::class,
        'Invitation' => InvitationModel::class,
        'Membership' => MembershipModel::class,
        'Organization' => OrganizationModel::class,
        'OrganizationDomain' => OrganizationDomainModel::class,
        'OrganizationLink' => OrganizationLinkModel::class,
        'Permission' => PermissionModel::class,
        'Profile' => ProfileModel::class,
        'Role' => RoleModel::class,
        'RoleAssignment' => RoleAssignmentModel::class,
        'ScimGroup' => ScimGroupModel::class,
        'ScimToken' => ScimTokenModel::class,
        'ScimUser' => ScimUserModel::class,
        'SsoConnection' => SsoConnectionModel::class,
        'SsoIdentity' => SsoIdentityModel::class,
        'Team' => TeamModel::class,
        'TeamMember' => TeamMemberModel::class,
        'Transfer' => TransferModel::class,
        'TransferRow' => TransferRowModel::class,
        'User' => UserModel::class,
    ];

    $cases = [];

    foreach ($models as $name => $model) {
        $cases[$name] = ['JayI\\Roster\\Models\\'.$name, $model];
    }

    return $cases;
}

it('resolves and writes each model under its pre-domain class name', function (string $stored, string $model): void {
    expect(Relation::getMorphedModel($stored))->toBe($model)
        ->and((new $model)->getMorphClass())->toBe($stored);
})->with(rosterMorphAliases());
