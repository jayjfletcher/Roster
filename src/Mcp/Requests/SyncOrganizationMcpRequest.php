<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\SyncOrganizationAction;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

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
