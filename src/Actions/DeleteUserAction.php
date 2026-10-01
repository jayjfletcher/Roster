<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\UserDeletedActionEvent;
use JayI\Roster\Events\Action\UserDeletingActionEvent;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Profile;
use JayI\Roster\Models\RoleAssignment;

final class DeleteUserAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Delete a user. A model using SoftDeletes keeps its profile, memberships
     * and personal organization so a restore brings it all back; a hard
     * delete removes them too. Owners of shared organizations must transfer
     * them first.
     */
    public function execute(Model $user, ?Model $actor = null): void
    {
        if ($actor !== null && $actor->is($user)) {
            throw ValidationException::withMessages([
                'user' => __('roster::roster.cannot_delete_self'),
            ]);
        }

        $owned = Organization::query()->where('owner_id', $user->getKey());

        if ((clone $owned)->where('personal', false)->exists()) {
            throw ValidationException::withMessages([
                'user' => __('roster::roster.cannot_delete_owner'),
            ]);
        }

        UserDeletingActionEvent::dispatch($user);

        DB::transaction(function () use ($user, $owned): void {
            if (! in_array(SoftDeletes::class, class_uses_recursive($user), true)) {
                $owned->get()->each(fn (Organization $organization) => app(DeleteOrganizationAction::class)->execute($organization, force: true));
                Membership::query()->where('user_id', $user->getKey())->delete();
                RoleAssignment::query()->where('user_id', $user->getKey())->delete();
                Profile::query()->where('user_id', $user->getKey())->delete();
            }

            $user->delete();
        });

        UserDeletedActionEvent::dispatch($user);
    }
}
