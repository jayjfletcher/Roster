<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Actions\Concerns\ManagesMemberships;
use JayI\Roster\Actions\Concerns\ResolvesInvitations;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Events\Action\InvitationAcceptedActionEvent;
use JayI\Roster\Events\Action\InvitationAcceptingActionEvent;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Users;

final class AcceptInvitationAction
{
    use ManagesMemberships;
    use ResolvesInvitations;

    public function __construct(private readonly Users $users) {}

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
     * Accept an invitation as `$user`, whose email must match it. Joins the
     * organization and every invited team that still exists, and makes the
     * organization current when the user has none yet.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, Model $user): Invitation
    {
        $invitation = $this->pendingInvitationFor($data['token'] ?? null, $user);

        InvitationAcceptingActionEvent::dispatch($invitation, $user);

        DB::transaction(function () use ($invitation, $user): void {
            /** @var Organization $organization */
            $organization = $invitation->organization;

            $membership = $this->join($organization, $user, MembershipSource::Invitation);

            $teams = Team::query()
                ->where('organization_id', $organization->getKey())
                ->whereIn('id', (array) $invitation->teams)
                ->get();

            foreach ($teams as $team) {
                $this->seat($team, $membership);
            }

            $profile = $this->users->profile($user);

            if ($profile->current_organization_id === null) {
                $profile->update(['current_organization_id' => $organization->getKey()]);
            }

            $invitation->update(['accepted_at' => now()]);
        });

        $invitation = $invitation->refresh()->load('organization');

        InvitationAcceptedActionEvent::dispatch($invitation, $user);

        return $invitation;
    }
}
