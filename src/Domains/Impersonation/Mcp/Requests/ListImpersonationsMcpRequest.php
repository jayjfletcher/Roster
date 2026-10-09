<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Impersonation\Actions\ListImpersonationsAction;
use RefactorCircus\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Mcp\Request;
use RefactorCircus\Roster\Support\Scopes;

final class ListImpersonationsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): OrganizationModel|TeamModel|null
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
