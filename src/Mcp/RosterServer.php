<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp;

use JayI\Roster\Cortex\CortexIntegration;
use JayI\Roster\Mcp\Tools\AcceptInvitationTool;
use JayI\Roster\Mcp\Tools\AddMemberTool;
use JayI\Roster\Mcp\Tools\AddTeamMemberTool;
use JayI\Roster\Mcp\Tools\AssignRoleTool;
use JayI\Roster\Mcp\Tools\CancelTransferTool;
use JayI\Roster\Mcp\Tools\ConfirmImportTool;
use JayI\Roster\Mcp\Tools\CreateInvitationTool;
use JayI\Roster\Mcp\Tools\CreateOrganizationTool;
use JayI\Roster\Mcp\Tools\CreatePermissionTool;
use JayI\Roster\Mcp\Tools\CreateRoleTool;
use JayI\Roster\Mcp\Tools\CreateScimTokenTool;
use JayI\Roster\Mcp\Tools\CreateSsoConnectionTool;
use JayI\Roster\Mcp\Tools\CreateTeamTool;
use JayI\Roster\Mcp\Tools\CreateUserTool;
use JayI\Roster\Mcp\Tools\DeactivateUserTool;
use JayI\Roster\Mcp\Tools\DeclineInvitationTool;
use JayI\Roster\Mcp\Tools\DeleteOrganizationTool;
use JayI\Roster\Mcp\Tools\DeletePermissionTool;
use JayI\Roster\Mcp\Tools\DeleteRoleTool;
use JayI\Roster\Mcp\Tools\DeleteSsoConnectionTool;
use JayI\Roster\Mcp\Tools\DeleteTeamTool;
use JayI\Roster\Mcp\Tools\DeleteUserTool;
use JayI\Roster\Mcp\Tools\JoinByDomainTool;
use JayI\Roster\Mcp\Tools\LinkOrganizationTool;
use JayI\Roster\Mcp\Tools\ListAuditEntriesTool;
use JayI\Roster\Mcp\Tools\ListImpersonationsTool;
use JayI\Roster\Mcp\Tools\ListInvitationsTool;
use JayI\Roster\Mcp\Tools\ListMembersTool;
use JayI\Roster\Mcp\Tools\ListOrganizationsTool;
use JayI\Roster\Mcp\Tools\ListPermissionsTool;
use JayI\Roster\Mcp\Tools\ListRoleAssignmentsTool;
use JayI\Roster\Mcp\Tools\ListRolesTool;
use JayI\Roster\Mcp\Tools\ListScimTokensTool;
use JayI\Roster\Mcp\Tools\ListSsoConnectionsTool;
use JayI\Roster\Mcp\Tools\ListSsoIdentitiesTool;
use JayI\Roster\Mcp\Tools\ListTeamsTool;
use JayI\Roster\Mcp\Tools\ListTransfersTool;
use JayI\Roster\Mcp\Tools\ListUserPermissionsTool;
use JayI\Roster\Mcp\Tools\ListUsersTool;
use JayI\Roster\Mcp\Tools\ReactivateUserTool;
use JayI\Roster\Mcp\Tools\RecordAuditEventTool;
use JayI\Roster\Mcp\Tools\RemoveMemberTool;
use JayI\Roster\Mcp\Tools\RemoveTeamMemberTool;
use JayI\Roster\Mcp\Tools\RevokeInvitationTool;
use JayI\Roster\Mcp\Tools\RevokeRoleTool;
use JayI\Roster\Mcp\Tools\RevokeScimTokenTool;
use JayI\Roster\Mcp\Tools\ShowAuditEntryTool;
use JayI\Roster\Mcp\Tools\ShowImportTemplateTool;
use JayI\Roster\Mcp\Tools\ShowOrganizationTool;
use JayI\Roster\Mcp\Tools\ShowRoleTool;
use JayI\Roster\Mcp\Tools\ShowSsoConnectionTool;
use JayI\Roster\Mcp\Tools\ShowTeamTool;
use JayI\Roster\Mcp\Tools\ShowTransferTool;
use JayI\Roster\Mcp\Tools\ShowUserTool;
use JayI\Roster\Mcp\Tools\StartExportTool;
use JayI\Roster\Mcp\Tools\StartImpersonationTool;
use JayI\Roster\Mcp\Tools\StartImportTool;
use JayI\Roster\Mcp\Tools\StopImpersonationTool;
use JayI\Roster\Mcp\Tools\SuspendUserTool;
use JayI\Roster\Mcp\Tools\SwitchContextTool;
use JayI\Roster\Mcp\Tools\SyncOrganizationsTool;
use JayI\Roster\Mcp\Tools\SyncOrganizationTool;
use JayI\Roster\Mcp\Tools\TransferOwnershipTool;
use JayI\Roster\Mcp\Tools\UnlinkOrganizationTool;
use JayI\Roster\Mcp\Tools\UnlinkSsoIdentityTool;
use JayI\Roster\Mcp\Tools\UpdateOrganizationTool;
use JayI\Roster\Mcp\Tools\UpdatePermissionTool;
use JayI\Roster\Mcp\Tools\UpdateProfileTool;
use JayI\Roster\Mcp\Tools\UpdateRoleTool;
use JayI\Roster\Mcp\Tools\UpdateSsoConnectionTool;
use JayI\Roster\Mcp\Tools\UpdateTeamTool;
use JayI\Roster\Mcp\Tools\UpdateUserTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;

#[Name('Roster')]
#[Version('1.0.0')]
#[Instructions(
    'Manage this application\'s users. A user is identified by the `id` returned from list-users-tool. '.
    'Each user has a profile (display name, avatar, timezone, locale, bio, meta) and a status: active, '.
    'suspended (a temporary block) or deactivated (a closed account that can be reopened). '.
    'Suspended and deactivated users are blocked from routes guarded by the roster.active middleware; '.
    'reactivate-user-tool returns either to active. You cannot suspend, deactivate or delete yourself. '.
    'Organizations (by slug) are tenants: users join them as members, and teams (slug unique within the '.
    'organization) group members. Each organization has one owner, who cannot be removed; transfer-ownership-tool '.
    'moves it. Invite people by email with create-invitation-tool; the recipient accepts through the emailed link. '.
    'A user\'s current organization and team are set with switch-context-tool. '.
    'Roles bundle permissions and are assigned globally, in an organization, or on a team; a user\'s permissions in '.
    'a scope are the union of their global, organization and team roles, and an organization\'s owner holds every '.
    'organization permission. Every tool needs a permission (e.g. roster.users.update) - list-user-permissions-tool '.
    'shows what a user holds. You can only grant permissions you hold yourself. '.
    'Every change is recorded in an append-only audit log (list-audit-entries-tool); the application can record '.
    'its own events there too (record-audit-event-tool). '.
    'Bulk changes go through CSV (show-import-template-tool gives each import type\'s columns): start-import-tool previews every row without changing anything, and only '.
    'confirm-import-tool applies it; start-export-tool builds a CSV whose download link show-transfer-tool returns. '.
    'Organizations kept in an external system (ERP, CRM) are synced by their id there with sync-organization-tool or '.
    'sync-organizations-tool, and found with list-organizations-tool (source, external_id, account_number).',
)]
final class RosterServer extends Server
{
    /**
     * Every tool the server offers, behind ToolSearch.
     *
     * @var array<int, class-string<Tool>>
     */
    public const array TOOLS = [
        // Users
        ListUsersTool::class,
        ShowUserTool::class,
        CreateUserTool::class,
        UpdateUserTool::class,
        DeleteUserTool::class,

        // Profile
        UpdateProfileTool::class,

        // Status
        SuspendUserTool::class,
        DeactivateUserTool::class,
        ReactivateUserTool::class,

        // Context
        SwitchContextTool::class,
        JoinByDomainTool::class,

        // Organizations
        ListOrganizationsTool::class,
        ShowOrganizationTool::class,
        CreateOrganizationTool::class,
        UpdateOrganizationTool::class,
        DeleteOrganizationTool::class,
        TransferOwnershipTool::class,
        SyncOrganizationTool::class,
        SyncOrganizationsTool::class,
        LinkOrganizationTool::class,
        UnlinkOrganizationTool::class,

        // Members
        ListMembersTool::class,
        AddMemberTool::class,
        RemoveMemberTool::class,

        // Teams
        ListTeamsTool::class,
        ShowTeamTool::class,
        CreateTeamTool::class,
        UpdateTeamTool::class,
        DeleteTeamTool::class,
        AddTeamMemberTool::class,
        RemoveTeamMemberTool::class,

        // Invitations
        ListInvitationsTool::class,
        CreateInvitationTool::class,
        RevokeInvitationTool::class,
        AcceptInvitationTool::class,
        DeclineInvitationTool::class,

        // Roles and permissions
        ListPermissionsTool::class,
        CreatePermissionTool::class,
        UpdatePermissionTool::class,
        DeletePermissionTool::class,
        ListRolesTool::class,
        ShowRoleTool::class,
        CreateRoleTool::class,
        UpdateRoleTool::class,
        DeleteRoleTool::class,
        ListRoleAssignmentsTool::class,
        AssignRoleTool::class,
        RevokeRoleTool::class,
        ListUserPermissionsTool::class,

        // Single sign-on
        ListSsoConnectionsTool::class,
        ShowSsoConnectionTool::class,
        CreateSsoConnectionTool::class,
        UpdateSsoConnectionTool::class,
        DeleteSsoConnectionTool::class,
        ListSsoIdentitiesTool::class,
        UnlinkSsoIdentityTool::class,

        // SCIM provisioning
        ListScimTokensTool::class,
        CreateScimTokenTool::class,
        RevokeScimTokenTool::class,

        // Impersonation
        StartImpersonationTool::class,
        ListImpersonationsTool::class,
        StopImpersonationTool::class,

        // Audit log
        ListAuditEntriesTool::class,
        ShowAuditEntryTool::class,
        RecordAuditEventTool::class,

        // CSV import and export
        ShowImportTemplateTool::class,
        StartImportTool::class,
        ConfirmImportTool::class,
        StartExportTool::class,
        ListTransfersTool::class,
        ShowTransferTool::class,
        CancelTransferTool::class,
    ];

    /**
     * @var array<class-string<ToolSearch>, array<int, class-string<Tool>|Tool>>
     */
    protected array $tools = [
        ToolSearch::class => self::TOOLS,
    ];

    /**
     * Serve Cortex's published instructions override, when Cortex is
     * installed and one is published, in place of the ones declared above.
     */
    public function createContext(): ServerContext
    {
        $context = parent::createContext();
        $override = app(CortexIntegration::class)->instructions();

        if ($override !== null) {
            $context->instructions = $override;
        }

        return $context;
    }
}
