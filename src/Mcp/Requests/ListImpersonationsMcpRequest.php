<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ListImpersonationsAction;
use JayI\Roster\Http\Resources\ImpersonationResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\ResponseFactory;

final class ListImpersonationsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): Organization|Team|null
    {
        return Scopes::fromInput($this->get('organization'));
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return ListImpersonationsAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $impersonations = app(ListImpersonationsAction::class)->execute($validated, $this->organizations());

        return $this->structuredCollection(ImpersonationResource::collection($impersonations->items())->resolve(), [
            'meta' => [
                'current_page' => $impersonations->currentPage(),
                'last_page' => $impersonations->lastPage(),
                'per_page' => $impersonations->perPage(),
                'total' => $impersonations->total(),
            ],
        ]);
    }
}
