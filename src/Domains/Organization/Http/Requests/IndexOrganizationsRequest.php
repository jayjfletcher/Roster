<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Actions\ListOrganizationsAction;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;
use JayI\Roster\Http\Request;

final class IndexOrganizationsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ListOrganizationsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return OrganizationResource::collection(app(ListOrganizationsAction::class)->execute($this->validated(), $this->organizations()))->response();
    }
}
