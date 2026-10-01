<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\RevokeRoleAction;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;
use Laravel\Mcp\Response;

final class RevokeRoleMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.assign';
    }

    protected function scope(): Organization|Team|null
    {
        return $this->assignment()->team ?? $this->assignment()->organization;
    }

    private function assignment(): RoleAssignment
    {
        return RoleAssignment::query()
            ->where('user_id', $this->targetUser()->getKey())
            ->whereKey($this->get('assignment'))
            ->firstOrFail();
    }

    protected function rules(): array
    {
        return RevokeRoleAction::rules() + $this->userRules() + ['assignment' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(RevokeRoleAction::class)->execute($this->assignment(), $this->actor());

        return Response::text('Role revoked.');
    }
}
