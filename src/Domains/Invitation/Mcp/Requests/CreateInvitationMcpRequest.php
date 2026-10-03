<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Mcp\Requests;

use JayI\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use JayI\Roster\Domains\Invitation\Resources\InvitationResource;
use JayI\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateInvitationMcpRequest extends OrganizationMcpRequest
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
        return CreateInvitationAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $invitation = app(CreateInvitationAction::class)->execute($this->organization(), $this->without($validated, 'organization'), $this->actor());

        return Response::structured(['data' => (new InvitationResource($invitation))->resolve()]);
    }
}
