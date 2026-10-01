<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Actions\RevokeRoleAction;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;

final class RevokeRoleRequest extends UserRequest
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
