<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\UpdateOrganizationAction;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Models\Organization;

final class UpdateOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return UpdateOrganizationAction::rules($this->organization());
    }

    public function persist(): JsonResponse
    {
        $organization = app(UpdateOrganizationAction::class)->execute($this->organization(), $this->validated());

        return (new OrganizationResource($organization))->response();
    }
}
