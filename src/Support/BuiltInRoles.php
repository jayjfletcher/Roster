<?php

declare(strict_types=1);

namespace JayI\Roster\Support;

use Illuminate\Support\Facades\DB;
use JayI\Roster\Enums\RoleScope;
use JayI\Roster\Models\Permission;
use JayI\Roster\Models\Role;

/**
 * Roster's own permissions and default roles.
 *
 * Seeded by the roles migration and re-synced by `roster:sync-permissions`.
 * Syncing adds anything missing and never removes or rewrites what an admin
 * has changed, so it is safe to run on every deploy.
 */
final class BuiltInRoles
{
    /**
     * @var array<string, string>
     */
    public const array PERMISSIONS = [
        'roster.users.view' => 'View users.',
        'roster.users.create' => 'Create users.',
        'roster.users.update' => 'Edit users, their profiles and context.',
        'roster.users.delete' => 'Delete and restore users.',
        'roster.users.purge' => 'Permanently delete deleted users.',
        'roster.users.manage-status' => 'Suspend, deactivate and reactivate users.',
        'roster.users.approve' => 'Approve or reject accounts awaiting approval.',
        'roster.users.impersonate' => 'Act as another user, temporarily and on the record.',
        'roster.organizations.view' => 'View organizations.',
        'roster.organizations.create' => 'Create organizations.',
        'roster.organizations.update' => "Edit an organization's settings.",
        'roster.organizations.delete' => 'Delete and restore organizations.',
        'roster.organizations.purge' => 'Permanently delete deleted organizations.',
        'roster.organizations.transfer' => 'Transfer organization ownership.',
        'roster.organizations.sync' => 'Create and update organizations from external systems.',
        'roster.members.view' => 'View organization members.',
        'roster.members.manage' => 'Add and remove organization members.',
        'roster.teams.view' => 'View teams and their members.',
        'roster.teams.manage' => 'Create, edit and delete teams and seat members.',
        'roster.invitations.view' => 'View invitations.',
        'roster.invitations.manage' => 'Send and revoke invitations.',
        'roster.roles.view' => 'View roles and permissions.',
        'roster.roles.manage' => 'Create, edit and delete roles and permissions.',
        'roster.roles.assign' => 'Assign and revoke roles.',
        'roster.sso.view' => "View an organization's single sign-on connections.",
        'roster.sso.manage' => 'Configure single sign-on connections.',
        'roster.scim.manage' => 'Issue and revoke SCIM provisioning tokens.',
        'roster.audit.view' => 'Read the audit log.',
        'roster.audit.record' => "Record the app's own events in the audit log.",
        'atrium.view' => 'Open the Atrium dashboard.',
    ];

    /**
     * Default roles: slug => [name, scope, super, permissions].
     *
     * @return array<string, array{name: string, scope: RoleScope, super: bool, permissions: array<int, string>}>
     */
    public static function roles(): array
    {
        $organizationWide = array_values(array_filter(
            array_keys(self::PERMISSIONS),
            fn (string $permission): bool => str_starts_with($permission, 'roster.')
                && ! str_starts_with($permission, 'roster.users.')
                && ! in_array($permission, ['roster.organizations.create', 'roster.organizations.sync', 'roster.organizations.purge'], true),
        ));

        return [
            'super-admin' => ['name' => 'Super admin', 'scope' => RoleScope::Global, 'super' => true, 'permissions' => []],
            'admin' => ['name' => 'Admin', 'scope' => RoleScope::Organization, 'super' => false, 'permissions' => $organizationWide],
            'member' => ['name' => 'Member', 'scope' => RoleScope::Organization, 'super' => false, 'permissions' => [
                'roster.organizations.view',
                'roster.members.view',
                'roster.teams.view',
            ]],
            'lead' => ['name' => 'Team lead', 'scope' => RoleScope::Team, 'super' => false, 'permissions' => [
                'roster.teams.view',
                'roster.teams.manage',
            ]],
        ];
    }

    public static function sync(): void
    {
        DB::transaction(function (): void {
            foreach (self::PERMISSIONS as $name => $description) {
                Permission::query()->firstOrCreate(['name' => $name], ['description' => $description, 'system' => true]);
            }

            foreach (self::roles() as $slug => $definition) {
                $role = Role::query()
                    ->whereNull('organization_id')
                    ->where('scope', $definition['scope'])
                    ->where('slug', $slug)
                    ->first();

                if ($role !== null) {
                    continue;
                }

                $role = Role::query()->create([
                    'name' => $definition['name'],
                    'slug' => $slug,
                    'scope' => $definition['scope'],
                    'super' => $definition['super'],
                    'system' => true,
                ]);

                $role->permissions()->sync(Permission::query()->whereIn('name', $definition['permissions'])->pluck('id'));
            }
        });
    }
}
