<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\CreateSsoConnectionAction;
use JayI\Roster\Http\Resources\SsoConnectionResource;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateSsoConnectionMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    protected function scope(): Organization
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
