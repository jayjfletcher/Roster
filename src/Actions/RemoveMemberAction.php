<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\Concerns\ManagesMemberships;
use JayI\Roster\Events\Action\MemberRemovedActionEvent;
use JayI\Roster\Events\Action\MemberRemovingActionEvent;
use JayI\Roster\Models\Organization;

final class RemoveMemberAction
{
    use ManagesMemberships;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Remove a member, and with them every team seat they held.
     */
    public function execute(Organization $organization, Model $user): void
    {
        if ($organization->isOwnedBy($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.cannot_remove_owner')]);
        }

        $membership = $organization->membershipFor($user)
            ?? throw ValidationException::withMessages(['user' => __('roster::roster.not_a_member')]);

        MemberRemovingActionEvent::dispatch($organization, $user);

        DB::transaction(function () use ($organization, $user, $membership): void {
            $membership->teams()->detach();
            $membership->delete();
            $this->revokeRoles($user, $organization);
            $this->forgetContext($user, $organization);
        });

        MemberRemovedActionEvent::dispatch($organization, $user);
    }
}
