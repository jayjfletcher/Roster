<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

abstract class OrganizationMcpRequest extends Request
{
    private ?OrganizationModel $resolvedOrganization = null;

    /**
     * The organization named by the `organization` slug argument.
     */
    protected function organization(): OrganizationModel
    {
        return $this->resolvedOrganization ??= OrganizationModel::query()
            ->where('slug', $this->get('organization'))
            ->firstOrFail();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function organizationRules(): array
    {
        return ['organization' => ['required', 'string']];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function without(array $validated, string ...$keys): array
    {
        return array_diff_key($validated, array_flip($keys));
    }

    protected function respondWithOrganization(OrganizationModel $organization): ResponseFactory
    {
        return Response::structured(['data' => (new OrganizationResource($organization))->resolve()]);
    }
}
