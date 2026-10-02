<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\RestoreOrganizationAction;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

/**
 * Works on deleted records too.
 */
final class RestoreOrganizationMcpRequest extends Request
{
    private ?Organization $resolved = null;

    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): Organization
    {
        return $this->trashed();
    }

    protected function rules(): array
    {
        return RestoreOrganizationAction::rules() + ['organization' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => (new OrganizationResource(app(RestoreOrganizationAction::class)->execute($this->trashed())))->resolve()]);
    }

    private function trashed(): Organization
    {
        return $this->resolved ??= Organization::withTrashed()->where('slug', $this->get('organization'))->firstOrFail();
    }
}
