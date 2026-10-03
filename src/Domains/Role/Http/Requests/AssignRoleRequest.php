<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Actions\AssignRoleAction;
use JayI\Roster\Domains\Role\Resources\RoleAssignmentResource;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\User\Http\Requests\UserRequest;
use JayI\Roster\Support\Scopes;

final class AssignRoleRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.roles.assign';
    }

    protected function scope(): OrganizationModel|TeamModel|null
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
