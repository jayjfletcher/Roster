<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\LinkOrganizationAction;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Models\Organization;

final class LinkOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function scope(): Organization
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
