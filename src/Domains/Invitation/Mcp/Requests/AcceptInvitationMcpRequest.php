<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Invitation\Actions\AcceptInvitationAction;

final class AcceptInvitationMcpRequest extends InvitationResponseMcpRequest
{
    protected function rules(): array
    {
        return AcceptInvitationAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithInvitation(app(AcceptInvitationAction::class)->execute($validated, $this->respondent()));
    }
}
