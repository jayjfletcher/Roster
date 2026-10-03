<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\UnlinkOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use Laravel\Mcp\ResponseFactory;

final class UnlinkOrganizationMcpRequest extends OrganizationMcpRequest
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
        return UnlinkOrganizationAction::rules() + $this->organizationRules() + ['source' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithOrganization(app(UnlinkOrganizationAction::class)->execute($this->organization(), (string) $validated['source']));
    }
}
