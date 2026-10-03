<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\AddMemberAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Organization\Resources\MemberResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class AddMemberMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.members.manage';
    }

    protected function scope(): OrganizationModel
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
