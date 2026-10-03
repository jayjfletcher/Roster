<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use Laravel\Mcp\ResponseFactory;

final class CreateOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.create';
    }

    protected function rules(): array
    {
        return CreateOrganizationAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithOrganization(app(CreateOrganizationAction::class)->execute($validated));
    }
}
