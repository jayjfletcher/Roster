<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Actions\Concerns\ResolvesInvitations;
use JayI\Roster\Events\Action\InvitationDeclinedActionEvent;
use JayI\Roster\Events\Action\InvitationDecliningActionEvent;
use JayI\Roster\Models\Invitation;

final class DeclineInvitationAction
{
    use ResolvesInvitations;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Decline an invitation as `$user`, whose email must match it.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, Model $user): Invitation
    {
        $invitation = $this->pendingInvitationFor($data['token'] ?? null, $user);

        InvitationDecliningActionEvent::dispatch($invitation, $user);

        DB::transaction(fn () => $invitation->update(['declined_at' => now()]));

        $invitation = $invitation->refresh()->load('organization');

        InvitationDeclinedActionEvent::dispatch($invitation, $user);

        return $invitation;
    }
}
