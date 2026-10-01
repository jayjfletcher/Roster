<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\RevokeInvitationAction;
use JayI\Roster\Http\Resources\InvitationResource;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class RevokeInvitationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.manage';
    }

    protected function scope(): Organization
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
