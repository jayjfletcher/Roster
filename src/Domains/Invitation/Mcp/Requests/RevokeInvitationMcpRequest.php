<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Invitation\Actions\RevokeInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class RevokeInvitationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.manage';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return RevokeInvitationAction::rules() + $this->organizationRules() + ['invitation' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $invitation = $this->organization()->invitations()->whereKey($validated['invitation'])->firstOrFail();

        return Response::structured(['data' => (new InvitationResource(app(RevokeInvitationAction::class)->execute($invitation)))->resolve()]);
    }
}
