<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Mcp\Requests;

use JayI\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Sso\Actions\CreateSsoConnectionAction;
use JayI\Roster\Domains\Sso\Resources\SsoConnectionResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateSsoConnectionMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return CreateSsoConnectionAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $connection = app(CreateSsoConnectionAction::class)->execute($this->organization(), $this->without($validated, 'organization'));

        return Response::structured(['data' => (new SsoConnectionResource($connection))->resolve()]);
    }
}
