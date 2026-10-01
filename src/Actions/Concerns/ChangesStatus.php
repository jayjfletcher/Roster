<?php

declare(strict_types=1);

namespace JayI\Roster\Actions\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Support\Users;

/**
 * Moves a user between statuses, guarding the transitions every status
 * Action shares.
 */
trait ChangesStatus
{
    private function guardTransition(Model $user, UserStatus $to, ?Model $actor): void
    {
        if ($actor !== null && $actor->is($user) && $to !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'user' => __('roster::roster.cannot_change_own_status'),
            ]);
        }

        if (app(Users::class)->status($user) === $to) {
            throw ValidationException::withMessages([
                'status' => __('roster::roster.already_status', ['status' => $to->value]),
            ]);
        }
    }

    private function changeStatus(Model $user, UserStatus $to, ?string $reason): void
    {
        app(Users::class)->profile($user)->update([
            'status' => $to,
            'status_reason' => $reason,
            'status_changed_at' => now(),
        ]);
    }
}
