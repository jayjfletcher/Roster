<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

abstract class OrganizationMcpRequest extends Request
{
    private ?Organization $resolvedOrganization = null;

    /**
     * The organization named by the `organization` slug argument.
     */
    protected function organization(): Organization
    {
        return $this->resolvedOrganization ??= Organization::query()
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

    protected function respondWithOrganization(Organization $organization): ResponseFactory
    {
        return Response::structured(['data' => (new OrganizationResource($organization))->resolve()]);
    }
}
