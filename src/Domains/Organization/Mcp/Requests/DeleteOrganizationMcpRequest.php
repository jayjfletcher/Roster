<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class DeleteOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): OrganizationModel
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
