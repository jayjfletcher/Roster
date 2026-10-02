<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\UserPurgedActionEvent;
use JayI\Roster\Events\Action\UserPurgingActionEvent;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Profile;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Support\Users;

final class PurgeUserAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Delete a user for good: their personal organization, memberships, role
     * assignments and profile go with them. A model that soft-deletes must be
     * deleted (trashed) first. Owners of shared organizations - deleted ones
     * too - must hand them over first.
     */
    public function execute(Model $user, ?Model $actor = null): void
    {
        if ($actor !== null && $actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.cannot_delete_self')]);
        }

        if ($this->users->softDeletes() && ! $this->users->trashed($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.delete_before_purge')]);
        }

        $owned = Organization::withTrashed()->where('owner_id', $user->getKey());

        if ((clone $owned)->where('personal', false)->exists()) {
            throw ValidationException::withMessages(['user' => __('roster::roster.cannot_delete_owner')]);
        }

        UserPurgingActionEvent::dispatch($user);

        DB::transaction(function () use ($user, $owned): void {
            $owned->get()->each(fn (Organization $organization) => app(PurgeOrganizationAction::class)->execute($organization, personal: true));
            Membership::query()->where('user_id', $user->getKey())->delete();
            RoleAssignment::query()->where('user_id', $user->getKey())->delete();
            Profile::query()->where('user_id', $user->getKey())->delete();

            $this->users->softDeletes() ? $user->forceDelete() : $user->delete();
        });

        UserPurgedActionEvent::dispatch($user);
    }
}
