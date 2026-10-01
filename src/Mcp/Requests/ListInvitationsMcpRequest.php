<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ListInvitationsAction;
use JayI\Roster\Http\Resources\InvitationResource;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\ResponseFactory;

final class ListInvitationsMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.view';
    }

    protected function scope(): Organization
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
