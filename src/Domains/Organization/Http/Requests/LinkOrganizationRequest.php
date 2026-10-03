<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Actions\LinkOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;

final class LinkOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['source' => $this->route('source')]);
    }

    public function rules(): array
    {
        return LinkOrganizationAction::rules();
    }

    public function persist(): JsonResponse
    {
        app(LinkOrganizationAction::class)->execute($this->organization(), $this->validated());

        return (new OrganizationResource($this->organization()->refresh()->load(['domains', 'links'])))->response();
    }
}
