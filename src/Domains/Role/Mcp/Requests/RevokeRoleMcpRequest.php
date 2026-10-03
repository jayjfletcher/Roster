<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Actions\RevokeRoleAction;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\User\Mcp\Requests\UserMcpRequest;
use Laravel\Mcp\Response;

final class RevokeRoleMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.assign';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return $this->assignment()->team ?? $this->assignment()->organization;
    }

    private function assignment(): RoleAssignmentModel
    {
        return RoleAssignmentModel::query()
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
