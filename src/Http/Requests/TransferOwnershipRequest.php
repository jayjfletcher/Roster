<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\TransferOwnershipAction;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Models\Organization;

final class TransferOwnershipRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.transfer';
    }

    protected function scope(): Organization
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
