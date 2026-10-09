<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Request;

final class SyncOrganizationMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.sync';
    }

    protected function rules(): array
    {
        return SyncOrganizationAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $result = app(SyncOrganizationAction::class)->execute($validated);

        return Response::structured(['data' => (new OrganizationResource($result->organization))->resolve(), 'outcome' => $result->outcome]);
    }
}
