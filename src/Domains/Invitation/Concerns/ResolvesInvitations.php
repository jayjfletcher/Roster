<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Invitation\Exceptions\InvalidInvitationException;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Services\InvitationTokens;
use RefactorCircus\Roster\Support\Users;

/**
 * Finds the pending invitation a token names, for the user answering it.
 */
trait ResolvesInvitations
{
    private function pendingInvitationFor(mixed $token, Model $user): InvitationModel
    {
        try {
            $invitation = InvitationTokens::find((string) $token);
        } catch (InvalidInvitationException) {
            throw ValidationException::withMessages(['token' => __('roster::roster.invitation_invalid')]);
        }

        // A deleted organization's invitations can't be answered.
        if ($invitation->organization === null) {
            throw ValidationException::withMessages(['token' => __('roster::roster.invitation_invalid')]);
        }

        if ($invitation->status() !== InvitationStatus::Pending) {
            throw ValidationException::withMessages([
                'token' => __('roster::roster.invitation_not_pending', ['status' => $invitation->status()->label()]),
            ]);
        }

        // The link alone is not enough: a forwarded email must not let
        // someone else in.
        if (strcasecmp((string) app(Users::class)->email($user), $invitation->email) !== 0) {
            throw ValidationException::withMessages(['token' => __('roster::roster.invitation_wrong_user')]);
        }

        return $invitation;
    }
}
