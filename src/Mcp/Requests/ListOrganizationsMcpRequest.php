<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ListOrganizationsAction;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class ListOrganizationsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    protected function rules(): array
    {
        return ListOrganizationsAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $organizations = app(ListOrganizationsAction::class)->execute($validated);

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
