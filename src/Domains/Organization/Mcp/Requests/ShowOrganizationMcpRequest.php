<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\ShowOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use Laravel\Mcp\ResponseFactory;

final class ShowOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return ShowOrganizationAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithOrganization(app(ShowOrganizationAction::class)->execute($this->organization()));
    }
}
