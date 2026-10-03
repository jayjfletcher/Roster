<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Invitation\Actions\DeclineInvitationAction;
use JayI\Roster\Domains\Invitation\Resources\InvitationResource;

final class DeclineInvitationRequest extends InvitationResponseRequest
{
    public function rules(): array
    {
        return DeclineInvitationAction::rules();
    }

    public function persist(): JsonResponse
    {
        $invitation = app(DeclineInvitationAction::class)->execute($this->validated(), $this->respondent());

        return (new InvitationResource($invitation))->response();
    }
}
