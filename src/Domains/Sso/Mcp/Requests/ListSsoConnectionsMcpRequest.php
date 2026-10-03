<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Mcp\Requests;

use JayI\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Sso\Actions\ListSsoConnectionsAction;
use JayI\Roster\Domains\Sso\Resources\SsoConnectionResource;
use Laravel\Mcp\ResponseFactory;

final class ListSsoConnectionsMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return ListSsoConnectionsAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $connections = app(ListSsoConnectionsAction::class)->execute($validated);

        return $this->structuredCollection(SsoConnectionResource::collection($connections->items())->resolve(), [
            'meta' => [
                'current_page' => $connections->currentPage(),
                'last_page' => $connections->lastPage(),
                'per_page' => $connections->perPage(),
                'total' => $connections->total(),
            ],
        ]);
    }
}
