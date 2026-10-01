<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\AssignRoleAction;
use JayI\Roster\Http\Resources\RoleAssignmentResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class AssignRoleMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.assign';
    }

    protected function scope(): Organization|Team|null
    {
        return Scopes::fromInput($this->get('organization'), $this->get('team'));
    }

    protected function rules(): array
    {
        return AssignRoleAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['user']);

        $assignment = app(AssignRoleAction::class)->execute($this->targetUser(), $validated, $this->actor());

        return Response::structured(['data' => (new RoleAssignmentResource($assignment))->resolve()]);
    }
}
