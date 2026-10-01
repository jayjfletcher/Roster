<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\UpdateRoleAction;
use JayI\Roster\Http\Resources\RoleResource;

final class UpdateRoleRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    public function rules(): array
    {
        return UpdateRoleAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new RoleResource(app(UpdateRoleAction::class)->execute($this->role(), $this->validated(), $this->actor())))->response();
    }
}
