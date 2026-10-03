<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Mcp\Requests;

use JayI\Roster\Domains\Invitation\Actions\DeclineInvitationAction;
use Laravel\Mcp\ResponseFactory;

final class DeclineInvitationMcpRequest extends InvitationResponseMcpRequest
{
    protected function rules(): array
    {
        return DeclineInvitationAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithInvitation(app(DeclineInvitationAction::class)->execute($validated, $this->respondent()));
    }
}
