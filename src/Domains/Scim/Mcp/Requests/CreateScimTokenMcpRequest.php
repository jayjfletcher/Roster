<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Mcp\Requests;

use JayI\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Scim\Actions\CreateScimTokenAction;
use JayI\Roster\Domains\Scim\Resources\ScimTokenResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateScimTokenMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return CreateScimTokenAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $issued = app(CreateScimTokenAction::class)->execute($this->organization(), $this->without($validated, 'organization'), $this->actor());

        return Response::structured(['data' => (new ScimTokenResource($issued->token))->resolve(), 'token' => $issued->plain]);
    }
}
