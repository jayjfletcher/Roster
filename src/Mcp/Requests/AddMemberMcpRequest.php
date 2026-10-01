<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Http\Resources\MemberResource;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class AddMemberMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.members.manage';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return AddMemberAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $membership = app(AddMemberAction::class)->execute($this->organization(), $this->without($validated, 'organization'));

        return Response::structured(['data' => (new MemberResource($membership))->resolve()]);
    }
}
