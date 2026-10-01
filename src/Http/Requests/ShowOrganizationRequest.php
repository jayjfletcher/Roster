<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ShowOrganizationAction;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Models\Organization;

final class ShowOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return ShowOrganizationAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new OrganizationResource(app(ShowOrganizationAction::class)->execute($this->organization())))->response();
    }
}
