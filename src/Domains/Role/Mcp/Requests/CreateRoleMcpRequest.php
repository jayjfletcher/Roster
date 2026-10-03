<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Actions\CreateRoleAction;
use JayI\Roster\Domains\Role\Resources\RoleResource;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateRoleMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->get('organization'), $this->get('team'));
    }

    protected function rules(): array
    {
        return CreateRoleAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => (new RoleResource(app(CreateRoleAction::class)->execute($validated, $this->actor())))->resolve()]);
    }
}
