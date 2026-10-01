<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\CreateRoleAction;
use JayI\Roster\Http\Resources\RoleResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateRoleMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function scope(): Organization|Team|null
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
