<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Services\Approvals;
use RefactorCircus\Roster\Support\Users;

/**
 * People who sign up through the app's own registration start with
 * `roster.users.registration_status`. Only ever active -> pending, and only
 * while nobody has changed the account's status yet.
 */
final class ApplyRegistrationStatus
{
    public function handle(Registered $event): void
    {
        $user = $event->user;
        $users = app(Users::class);

        if (! $user instanceof Model || ! is_a($user, $users->model()) || config('roster.users.registration_status') !== UserStatus::Pending->value) {
            return;
        }

        $profile = $users->profile($user);

        if ($profile->status !== UserStatus::Active || $profile->status_changed_at !== null) {
            return;
        }

        $profile->update(['status' => UserStatus::Pending, 'status_changed_at' => now()]);

        app(Approvals::class)->pending($user);
    }
}
