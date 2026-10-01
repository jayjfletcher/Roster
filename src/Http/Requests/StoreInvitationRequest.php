<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\CreateInvitationAction;
use JayI\Roster\Http\Resources\InvitationResource;
use JayI\Roster\Models\Organization;

final class StoreInvitationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.manage';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return CreateInvitationAction::rules();
    }

    public function persist(): JsonResponse
    {
        $invitation = app(CreateInvitationAction::class)->execute($this->organization(), $this->validated(), $this->actor());

        return (new InvitationResource($invitation))->response()->setStatusCode(201);
    }
}
