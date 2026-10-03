<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\TransferOwnershipAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use Laravel\Mcp\ResponseFactory;

final class TransferOwnershipMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.transfer';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return TransferOwnershipAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $data = $this->without($validated, 'organization');

        return $this->respondWithOrganization(app(TransferOwnershipAction::class)->execute($this->organization(), $data));
    }
}
