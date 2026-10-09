<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\LinkOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class LinkOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function scope(): OrganizationModel
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
