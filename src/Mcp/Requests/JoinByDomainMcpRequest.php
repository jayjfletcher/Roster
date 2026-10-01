<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\JoinOrganizationsByDomainAction;
use JayI\Roster\Http\Resources\OrganizationResource;
use Laravel\Mcp\ResponseFactory;

final class JoinByDomainMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    protected function rules(): array
    {
        return JoinOrganizationsByDomainAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $organizations = app(JoinOrganizationsByDomainAction::class)->execute($this->targetUser());

        return $this->structuredCollection(OrganizationResource::collection($organizations)->resolve());
    }
}
