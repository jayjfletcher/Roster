<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\CreateOrganizationAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\OrganizationResource;

final class StoreOrganizationRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.create';
    }

    public function rules(): array
    {
        return CreateOrganizationAction::rules();
    }

    public function persist(): JsonResponse
    {
        $organization = app(CreateOrganizationAction::class)->execute($this->validated());

        return (new OrganizationResource($organization))->response()->setStatusCode(201);
    }
}
