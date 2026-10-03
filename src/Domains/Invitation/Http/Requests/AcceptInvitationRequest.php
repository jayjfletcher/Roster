<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Invitation\Actions\AcceptInvitationAction;
use JayI\Roster\Domains\Invitation\Resources\InvitationResource;

final class AcceptInvitationRequest extends InvitationResponseRequest
{
    public function rules(): array
    {
        return AcceptInvitationAction::rules();
    }

    public function persist(): JsonResponse
    {
        $invitation = app(AcceptInvitationAction::class)->execute($this->validated(), $this->respondent());

        return (new InvitationResource($invitation))->response();
    }
}
