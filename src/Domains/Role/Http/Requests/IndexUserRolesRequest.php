<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Role\Actions\ListRoleAssignmentsAction;
use JayI\Roster\Domains\Role\Resources\RoleAssignmentResource;
use JayI\Roster\Domains\User\Http\Requests\UserRequest;

final class IndexUserRolesRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return ListRoleAssignmentsAction::rules();
    }

    public function persist(): JsonResponse
    {
        $filters = ['user' => $this->targetUser()->getRouteKey()] + $this->validated();

        return RoleAssignmentResource::collection(app(ListRoleAssignmentsAction::class)->execute($filters))->response();
    }
}
