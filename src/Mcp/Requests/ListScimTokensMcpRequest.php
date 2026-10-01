<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ListScimTokensAction;
use JayI\Roster\Http\Resources\ScimTokenResource;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\ResponseFactory;

final class ListScimTokensMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): Organization
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
