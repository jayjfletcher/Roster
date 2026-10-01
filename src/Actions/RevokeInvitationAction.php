<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Enums\InvitationStatus;
use JayI\Roster\Events\Action\InvitationRevokedActionEvent;
use JayI\Roster\Events\Action\InvitationRevokingActionEvent;
use JayI\Roster\Models\Invitation;

final class RevokeInvitationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(Invitation $invitation): Invitation
    {
        if ($invitation->status() !== InvitationStatus::Pending) {
            throw ValidationException::withMessages([
                'invitation' => __('roster::roster.invitation_not_pending', ['status' => $invitation->status()->label()]),
            ]);
        }

        InvitationRevokingActionEvent::dispatch($invitation);

        DB::transaction(fn () => $invitation->update(['revoked_at' => now()]));

        $invitation = $invitation->refresh()->load('organization');

        InvitationRevokedActionEvent::dispatch($invitation);

        return $invitation;
    }
}
