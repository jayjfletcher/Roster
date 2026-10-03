<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Role\Actions\ListRoleAssignmentsAction;
use JayI\Roster\Domains\Role\Resources\RoleAssignmentResource;
use JayI\Roster\Domains\User\Mcp\Requests\UserMcpRequest;
use Laravel\Mcp\ResponseFactory;

final class ListRoleAssignmentsMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return ListRoleAssignmentsAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $assignments = app(ListRoleAssignmentsAction::class)->execute(['user' => $this->targetUser()->getRouteKey()] + $validated);

        return $this->structuredCollection(RoleAssignmentResource::collection($assignments->items())->resolve(), [
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ]);
    }
}
