<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListOrganizationsAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\OrganizationResource;

final class IndexOrganizationsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    public function rules(): array
    {
        return ListOrganizationsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return OrganizationResource::collection(app(ListOrganizationsAction::class)->execute($this->validated()))->response();
    }
}
