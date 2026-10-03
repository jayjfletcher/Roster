<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Actions\RevokeRoleAction;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\User\Http\Requests\UserRequest;

final class RevokeRoleRequest extends UserRequest
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
            ->whereKey($this->route('assignment'))
            ->firstOrFail();
    }

    public function rules(): array
    {
        return RevokeRoleAction::rules();
    }

    public function persist(): Response
    {
        app(RevokeRoleAction::class)->execute($this->assignment(), $this->actor());

        return response()->noContent();
    }
}
