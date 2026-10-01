<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\RevokeInvitationAction;
use JayI\Roster\Http\Resources\InvitationResource;
use JayI\Roster\Models\Organization;

final class RevokeInvitationRequest extends OrganizationRequest
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
        return RevokeInvitationAction::rules();
    }

    public function persist(): JsonResponse
    {
        $invitation = $this->organization()->invitations()->whereKey($this->route('invitation'))->firstOrFail();

        return (new InvitationResource(app(RevokeInvitationAction::class)->execute($invitation)))->response();
    }
}
