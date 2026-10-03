<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Actions\TransferOwnershipAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;

final class TransferOwnershipRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.transfer';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return TransferOwnershipAction::rules();
    }

    public function persist(): JsonResponse
    {
        $organization = app(TransferOwnershipAction::class)->execute($this->organization(), $this->validated());

        return (new OrganizationResource($organization))->response();
    }
}
