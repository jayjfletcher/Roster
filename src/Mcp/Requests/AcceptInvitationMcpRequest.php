<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\AcceptInvitationAction;
use Laravel\Mcp\ResponseFactory;

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
