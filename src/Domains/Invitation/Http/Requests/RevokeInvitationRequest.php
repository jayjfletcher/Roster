<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Invitation\Actions\RevokeInvitationAction;
use JayI\Roster\Domains\Invitation\Resources\InvitationResource;
use JayI\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

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
