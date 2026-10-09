<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Request;

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
