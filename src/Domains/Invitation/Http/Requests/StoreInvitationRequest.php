<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use JayI\Roster\Domains\Invitation\Resources\InvitationResource;
use JayI\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

final class StoreInvitationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.manage';
    }

    protected function scope(): OrganizationModel
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
