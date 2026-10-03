<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Domains\Audit\Actions\ListAuditEntriesAction;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Audit\Services\AuditLog;
use JayI\Roster\Domains\Impersonation\Models\ImpersonationModel;
use JayI\Roster\Domains\Invitation\Models\InvitationModel;
use JayI\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
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
        'AuditEntry' => AuditEntryModel::class,
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

it('verifies an audit chain started before the refactor and continued after it', function (): void {
    $organization = organization();

    // An entry exactly as the pre-domain Roster wrote it: the subject type is
    // the old model class name, and the hash covers that stored value.
    DB::table('roster_audit_entries')->delete();
    $createdAt = now()->subDay()->startOfSecond();
    $row = [
        'source' => 'roster',
        'action' => 'organization.updated',
        'actor_id' => null,
        'subject_type' => 'JayI\\Roster\\Models\\Organization',
        'subject_id' => (string) $organization->getKey(),
        'subject_label' => 'Acme',
        'organization_id' => $organization->getKey(),
        'surface' => 'console',
        'ip' => null,
        'user_agent' => null,
        'changes' => [],
        'context' => [],
        'previous_hash' => null,
    ];
    $hash = hash('sha256', (string) json_encode([...$row, 'created_at' => $createdAt->getTimestamp()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));

    $id = DB::table('roster_audit_entries')->insertGetId([
        ...$row,
        'changes' => json_encode([]),
        'context' => json_encode([]),
        'hash' => $hash,
        'created_at' => $createdAt,
    ]);
    DB::table('roster_audit_chain')->where('id', 1)->update(['head_hash' => $hash]);

    expect(app(AuditLog::class)->verify())->toBeNull();

    // New entries about the same organization store the same subject type
    // and chain onto the old entry.
    $organization->refresh();
    app(UpdateOrganizationAction::class)->execute($organization, ['name' => 'Acme Ltd']);

    $latest = AuditEntryModel::query()->orderByDesc('id')->firstOrFail();

    expect($latest->subject_type)->toBe('JayI\\Roster\\Models\\Organization')
        ->and($latest->previous_hash)->toBe($hash)
        ->and(app(AuditLog::class)->verify())->toBeNull()
        ->and(app(ListAuditEntriesAction::class)->execute(['subject_type' => 'organization'])->pluck('id')->all())->toContain($id, $latest->id);

    $this->artisan('roster:verify-audit')->assertSuccessful();
});
