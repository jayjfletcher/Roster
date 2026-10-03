<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\ListOrganizationsAction;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class ListOrganizationsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return ListOrganizationsAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $organizations = app(ListOrganizationsAction::class)->execute($validated, $this->organizations());

        return $this->structuredCollection(OrganizationResource::collection($organizations->items())->resolve(), [
            'meta' => [
                'current_page' => $organizations->currentPage(),
                'last_page' => $organizations->lastPage(),
                'per_page' => $organizations->perPage(),
                'total' => $organizations->total(),
            ],
        ]);
    }
}
