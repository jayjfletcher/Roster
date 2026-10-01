<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\AssignRoleAction;
use JayI\Roster\Http\Resources\RoleAssignmentResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;

final class AssignRoleRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.roles.assign';
    }

    protected function scope(): Organization|Team|null
    {
        return Scopes::fromInput($this->input('organization'), $this->input('team'));
    }

    public function rules(): array
    {
        return AssignRoleAction::rules();
    }

    public function persist(): JsonResponse
    {
        $assignment = app(AssignRoleAction::class)->execute($this->targetUser(), $this->validated(), $this->actor());

        return (new RoleAssignmentResource($assignment))->response()->setStatusCode(201);
    }
}
