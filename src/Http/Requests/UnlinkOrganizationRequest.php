<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\UnlinkOrganizationAction;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Models\Organization;

final class UnlinkOrganizationRequest extends OrganizationRequest
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
        return UnlinkOrganizationAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new OrganizationResource(app(UnlinkOrganizationAction::class)->execute($this->organization(), (string) $this->route('source'))))->response();
    }
}
