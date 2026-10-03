<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;

final class UpdateOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function scope(): OrganizationModel
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
