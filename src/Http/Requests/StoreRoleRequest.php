<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\CreateRoleAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\RoleResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;

final class StoreRoleRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function scope(): Organization|Team|null
    {
        return Scopes::fromInput($this->input('organization'), $this->input('team'));
    }

    public function rules(): array
    {
        return CreateRoleAction::rules();
    }

    public function persist(): JsonResponse
    {
        $role = app(CreateRoleAction::class)->execute($this->validated(), $this->actor());

        return (new RoleResource($role))->response()->setStatusCode(201);
    }
}
