<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Actions\UnlinkOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;

final class UnlinkOrganizationRequest extends OrganizationRequest
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
        return UnlinkOrganizationAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new OrganizationResource(app(UnlinkOrganizationAction::class)->execute($this->organization(), (string) $this->route('source'))))->response();
    }
}
