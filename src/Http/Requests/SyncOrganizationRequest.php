<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\SyncOrganizationAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Support\OrganizationSyncResult;

/**
 * Upsert one organization by its record in an external system, named in
 * the URL: `PUT roster/organizations/external/{source}/{externalId}`.
 */
final class SyncOrganizationRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.sync';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['source' => $this->route('source'), 'external_id' => $this->route('externalId')]);
    }

    public function rules(): array
    {
        return SyncOrganizationAction::rules();
    }

    public function persist(): JsonResponse
    {
        $result = app(SyncOrganizationAction::class)->execute($this->validated());

        return (new OrganizationResource($result->organization))
            ->additional(['outcome' => $result->outcome])
            ->response()
            ->setStatusCode($result->outcome === OrganizationSyncResult::CREATED ? 201 : 200);
    }
}
