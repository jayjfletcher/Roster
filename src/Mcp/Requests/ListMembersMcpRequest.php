<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ListMembersAction;
use JayI\Roster\Http\Resources\MemberResource;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\ResponseFactory;

final class ListMembersMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.members.view';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return ListMembersAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $members = app(ListMembersAction::class)->execute($this->organization(), $this->without($validated, 'organization'));

        return $this->structuredCollection(MemberResource::collection($members->items())->resolve(), [
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ]);
    }
}
