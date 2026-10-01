<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\DeleteOrganizationAction;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;

final class DeleteOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return DeleteOrganizationAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): Response
    {
        app(DeleteOrganizationAction::class)->execute($this->organization());

        return Response::text('Organization deleted.');
    }
}
