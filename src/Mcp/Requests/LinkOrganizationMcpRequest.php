<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\LinkOrganizationAction;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\ResponseFactory;

final class LinkOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return LinkOrganizationAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        app(LinkOrganizationAction::class)->execute($this->organization(), $this->without($validated, 'organization'));

        return $this->respondWithOrganization($this->organization()->refresh()->load(['domains', 'links']));
    }
}
