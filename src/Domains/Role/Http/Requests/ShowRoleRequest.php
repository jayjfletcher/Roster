<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Role\Actions\ShowRoleAction;
use JayI\Roster\Domains\Role\Resources\RoleResource;

final class ShowRoleRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    public function rules(): array
    {
        return ShowRoleAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new RoleResource(app(ShowRoleAction::class)->execute($this->role())))->response();
    }
}
