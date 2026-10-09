<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Invitation\Actions\RevokeInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class RevokeInvitationRequest extends OrganizationRequest
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
        return RevokeInvitationAction::rules();
    }

    public function persist(): JsonResponse
    {
        $invitation = $this->organization()->invitations()->whereKey($this->route('invitation'))->firstOrFail();

        return (new InvitationResource(app(RevokeInvitationAction::class)->execute($invitation)))->response();
    }
}
