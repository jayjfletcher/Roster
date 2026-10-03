<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\RestoreOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

/**
 * Works on deleted records too.
 */
final class RestoreOrganizationMcpRequest extends Request
{
    private ?OrganizationModel $resolved = null;

    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): OrganizationModel
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

    private function trashed(): OrganizationModel
    {
        return $this->resolved ??= OrganizationModel::withTrashed()->where('slug', $this->get('organization'))->firstOrFail();
    }
}
