<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\UnlinkOrganizationAction;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\ResponseFactory;

final class UnlinkOrganizationMcpRequest extends OrganizationMcpRequest
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
        return UnlinkOrganizationAction::rules() + $this->organizationRules() + ['source' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithOrganization(app(UnlinkOrganizationAction::class)->execute($this->organization(), (string) $validated['source']));
    }
}
