<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Actions\ListRolesAction;
use JayI\Roster\Domains\Role\Resources\RoleResource;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Http\Request;
use JayI\Roster\Support\Scopes;

final class IndexRolesRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->input('organization'), $this->input('team'));
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ListRolesAction::rules();
    }

    public function persist(): JsonResponse
    {
        return RoleResource::collection(app(ListRolesAction::class)->execute($this->validated(), $this->organizations()))->response();
    }
}
