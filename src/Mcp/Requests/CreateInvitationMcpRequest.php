<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\CreateInvitationAction;
use JayI\Roster\Http\Resources\InvitationResource;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateInvitationMcpRequest extends OrganizationMcpRequest
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
        return CreateInvitationAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $invitation = app(CreateInvitationAction::class)->execute($this->organization(), $this->without($validated, 'organization'), $this->actor());

        return Response::structured(['data' => (new InvitationResource($invitation))->resolve()]);
    }
}
