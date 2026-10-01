<?php

declare(strict_types=1);

use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Mcp\Tools\AssignRoleTool;
use JayI\Roster\Mcp\Tools\CreatePermissionTool;
use JayI\Roster\Mcp\Tools\CreateRoleTool;
use JayI\Roster\Mcp\Tools\DeletePermissionTool;
use JayI\Roster\Mcp\Tools\DeleteRoleTool;
use JayI\Roster\Mcp\Tools\ListPermissionsTool;
use JayI\Roster\Mcp\Tools\ListRoleAssignmentsTool;
use JayI\Roster\Mcp\Tools\ListRolesTool;
use JayI\Roster\Mcp\Tools\ListUserPermissionsTool;
use JayI\Roster\Mcp\Tools\RevokeRoleTool;
use JayI\Roster\Mcp\Tools\ShowRoleTool;
use JayI\Roster\Mcp\Tools\UpdatePermissionTool;
use JayI\Roster\Mcp\Tools\UpdateRoleTool;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;

it('manages permissions with parity to the http payload', function (): void {
    mcpTool(CreatePermissionTool::class, ['name' => 'invoices.edit'])->assertOk();
    mcpTool(UpdatePermissionTool::class, ['name' => 'invoices.edit', 'description' => 'Edit'])->assertOk();

    $http = test()->getJson(route('roster.permissions.index', ['search' => 'invoices']))->json();

    mcpTool(ListPermissionsTool::class, ['search' => 'invoices'])->assertOk()->assertStructuredContent([
        'data' => $http['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 1],
    ]);

    mcpTool(DeletePermissionTool::class, ['name' => 'invoices.edit'])->assertOk()->assertSee('Permission deleted.');
});

it('manages roles with parity to the http payload', function (): void {
    mcpTool(CreateRoleTool::class, ['name' => 'Auditor', 'scope' => 'global', 'permissions' => ['roster.users.view']])->assertOk();
    $role = Role::query()->where('slug', 'auditor')->sole();
    $http = fn (): array => test()->getJson(route('roster.roles.show', $role->id))->json();

    mcpTool(ShowRoleTool::class, ['role' => $role->id])->assertOk()->assertStructuredContent($http());
    mcpTool(UpdateRoleTool::class, ['role' => $role->id, 'name' => 'Auditors'])->assertOk()->assertStructuredContent($http());

    $list = test()->getJson(route('roster.roles.index', ['scope' => 'global']))->json();
    mcpTool(ListRolesTool::class, ['scope' => 'global'])->assertOk()->assertStructuredContent([
        'data' => $list['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 2],
    ]);

    mcpTool(DeleteRoleTool::class, ['role' => $role->id])->assertOk()->assertSee('Role deleted.');
});

it('assigns, lists and revokes roles', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $ada->getRouteKey()]);
    $admin = Role::query()->where('slug', 'admin')->sole();

    mcpTool(AssignRoleTool::class, ['user' => $ada->getRouteKey(), 'role' => $admin->id, 'organization' => 'acme'])->assertOk();

    $http = test()->getJson(route('roster.users.roles.index', $ada->getRouteKey()))->json();
    mcpTool(ListRoleAssignmentsTool::class, ['user' => $ada->getRouteKey()])->assertOk()->assertStructuredContent([
        'data' => $http['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 2],
    ]);

    mcpTool(ListUserPermissionsTool::class, ['user' => $ada->getRouteKey(), 'organization' => 'acme'])
        ->assertOk()
        ->assertStructuredContent(test()->getJson(route('roster.users.permissions', [$ada->getRouteKey(), 'organization' => 'acme']))->json());

    $assignment = RoleAssignment::query()->where('role_id', $admin->id)->sole();
    mcpTool(RevokeRoleTool::class, ['user' => $ada->getRouteKey(), 'assignment' => $assignment->id])->assertOk()->assertSee('Role revoked.');
});
