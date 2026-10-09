<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Mcp\Request;

/**
 * Works on deleted records too.
 */
final class PurgeOrganizationMcpRequest extends Request
{
    private ?OrganizationModel $resolved = null;

    protected function ability(): string
    {
        return 'roster.organizations.purge';
    }

    protected function scope(): OrganizationModel
    {
        return $this->trashed();
    }

    protected function rules(): array
    {
        return PurgeOrganizationAction::rules() + ['organization' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(PurgeOrganizationAction::class)->execute($this->trashed());

        return Response::text('Deleted permanently.');
    }

    private function trashed(): OrganizationModel
    {
        return $this->resolved ??= OrganizationModel::withTrashed()->where('slug', $this->get('organization'))->firstOrFail();
    }
}
