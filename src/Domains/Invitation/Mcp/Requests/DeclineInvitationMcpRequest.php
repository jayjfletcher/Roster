<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Invitation\Actions\DeclineInvitationAction;

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
