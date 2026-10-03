<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Mcp\Requests;

use JayI\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Scim\Actions\ListScimTokensAction;
use JayI\Roster\Domains\Scim\Resources\ScimTokenResource;
use Laravel\Mcp\ResponseFactory;

final class ListScimTokensMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return ListScimTokensAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $tokens = app(ListScimTokensAction::class)->execute($this->organization(), $this->without($validated, 'organization'));

        return $this->structuredCollection(ScimTokenResource::collection($tokens->items())->resolve(), [
            'meta' => [
                'current_page' => $tokens->currentPage(),
                'last_page' => $tokens->lastPage(),
                'per_page' => $tokens->perPage(),
                'total' => $tokens->total(),
            ],
        ]);
    }
}
