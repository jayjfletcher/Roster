<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use JayI\Roster\Domains\Organization\Actions\RemoveMemberAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Support\Users;
use Laravel\Mcp\Response;

final class RemoveMemberMcpRequest extends OrganizationMcpRequest
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
        return RemoveMemberAction::rules() + $this->organizationRules() + ['user' => ['required']];
    }

    protected function handle(array $validated): Response
    {
        app(RemoveMemberAction::class)->execute($this->organization(), app(Users::class)->findOrFail($validated['user']));

        return Response::text('Member removed.');
    }
}
