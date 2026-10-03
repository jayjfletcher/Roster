<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Actions\CreateRoleAction;
use JayI\Roster\Domains\Role\Resources\RoleResource;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Http\Request;
use JayI\Roster\Support\Scopes;

final class StoreRoleRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function scope(): OrganizationModel|TeamModel|null
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
