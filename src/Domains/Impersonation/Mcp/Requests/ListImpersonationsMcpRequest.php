<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation\Mcp\Requests;

use JayI\Roster\Domains\Impersonation\Actions\ListImpersonationsAction;
use JayI\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\ResponseFactory;

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
