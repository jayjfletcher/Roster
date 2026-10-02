<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\SyncOrganizationsAction;
use JayI\Roster\Http\Resources\OrganizationSyncResults;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class SyncOrganizationsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.sync';
    }

    protected function rules(): array
    {
        return SyncOrganizationsAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->structuredCollection(OrganizationSyncResults::present(app(SyncOrganizationsAction::class)->execute($validated)));
    }
}
