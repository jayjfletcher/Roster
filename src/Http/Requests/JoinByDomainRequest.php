<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\JoinOrganizationsByDomainAction;
use JayI\Roster\Http\Resources\OrganizationResource;

final class JoinByDomainRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    public function rules(): array
    {
        return JoinOrganizationsByDomainAction::rules();
    }

    public function persist(): JsonResponse
    {
        return OrganizationResource::collection(app(JoinOrganizationsByDomainAction::class)->execute($this->targetUser()))->response();
    }
}
