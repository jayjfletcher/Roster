<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;
use JayI\Roster\Domains\User\Mcp\Requests\UserMcpRequest;
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
