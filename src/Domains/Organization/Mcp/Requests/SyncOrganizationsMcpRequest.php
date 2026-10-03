<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\SyncOrganizationsAction;
use JayI\Roster\Domains\Organization\Resources\OrganizationSyncResults;
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
