<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Mcp\Requests;

use JayI\Roster\Domains\Invitation\Actions\ListInvitationsAction;
use JayI\Roster\Domains\Invitation\Resources\InvitationResource;
use JayI\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use Laravel\Mcp\ResponseFactory;

final class ListInvitationsMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.view';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return ListInvitationsAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $invitations = app(ListInvitationsAction::class)->execute($this->organization(), $this->without($validated, 'organization'));

        return $this->structuredCollection(InvitationResource::collection($invitations->items())->resolve(), [
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
            ],
        ]);
    }
}
