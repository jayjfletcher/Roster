<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use Laravel\Mcp\ResponseFactory;

final class UpdateOrganizationMcpRequest extends OrganizationMcpRequest
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
        return UpdateOrganizationAction::rules($this->organization()) + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $data = $this->without($validated, 'organization');

        return $this->respondWithOrganization(app(UpdateOrganizationAction::class)->execute($this->organization(), $data));
    }
}
