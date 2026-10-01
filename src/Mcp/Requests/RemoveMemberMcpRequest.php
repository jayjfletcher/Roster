<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\RemoveMemberAction;
use JayI\Roster\Models\Organization;
use JayI\Roster\Support\Users;
use Laravel\Mcp\Response;

final class RemoveMemberMcpRequest extends OrganizationMcpRequest
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
        return RemoveMemberAction::rules() + $this->organizationRules() + ['user' => ['required']];
    }

    protected function handle(array $validated): Response
    {
        app(RemoveMemberAction::class)->execute($this->organization(), app(Users::class)->findOrFail($validated['user']));

        return Response::text('Member removed.');
    }
}
