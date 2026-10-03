<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Domains\Invitation\Concerns\ResolvesInvitations;
use JayI\Roster\Domains\Invitation\Events\InvitationAcceptedActionEvent;
use JayI\Roster\Domains\Invitation\Events\InvitationAcceptingActionEvent;
use JayI\Roster\Domains\Invitation\Models\InvitationModel;
use JayI\Roster\Domains\Organization\Concerns\ManagesMemberships;
use JayI\Roster\Domains\Organization\Enums\MembershipSource;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
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
    public function execute(array $data, Model $user): InvitationModel
    {
        $invitation = $this->pendingInvitationFor($data['token'] ?? null, $user);

        InvitationAcceptingActionEvent::dispatch($invitation, $user);

        DB::transaction(function () use ($invitation, $user): void {
            /** @var OrganizationModel $organization */
            $organization = $invitation->organization;

            $membership = $this->join($organization, $user, MembershipSource::Invitation);

            $teams = TeamModel::query()
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
