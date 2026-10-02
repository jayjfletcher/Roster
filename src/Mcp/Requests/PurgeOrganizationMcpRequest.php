<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\PurgeOrganizationAction;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;

/**
 * Works on deleted records too.
 */
final class PurgeOrganizationMcpRequest extends Request
{
    private ?Organization $resolved = null;

    protected function ability(): string
    {
        return 'roster.organizations.purge';
    }

    protected function scope(): Organization
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

    private function trashed(): Organization
    {
        return $this->resolved ??= Organization::withTrashed()->where('slug', $this->get('organization'))->firstOrFail();
    }
}
