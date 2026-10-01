<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Roster\Actions\AcceptInvitationAction;
use JayI\Roster\Actions\DeclineInvitationAction;
use JayI\Roster\Exceptions\InvalidInvitationException;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\InvitationTokens;

/**
 * The page an invitation email links to, for the invited user rather than
 * an admin. Answering acts as the signed-in user, whose email must match.
 */
final class InvitationWebController
{
    public function show(string $token): View
    {
        $invitation = $this->find($token);

        /** @var view-string $view */
        $view = 'roster::invitations.show';

        return view($view, [
            'invitation' => $invitation,
            'organization' => $invitation->organization,
            'teams' => Team::query()->whereIn('id', (array) $invitation->teams)->orderBy('name')->pluck('name'),
            'token' => $token,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = app(AcceptInvitationAction::class)->execute(
            $request->merge(['token' => $token])->validate(AcceptInvitationAction::rules()),
            $this->user($request),
        );

        return redirect((string) config('roster.invitations.redirect', '/'))
            ->with('status', __('roster::roster.invitation_accepted_flash', ['organization' => $invitation->organization?->name]));
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        app(DeclineInvitationAction::class)->execute(
            $request->merge(['token' => $token])->validate(DeclineInvitationAction::rules()),
            $this->user($request),
        );

        return redirect((string) config('roster.invitations.redirect', '/'))
            ->with('status', __('roster::roster.invitation_declined_flash'));
    }

    private function find(string $token): Invitation
    {
        try {
            return InvitationTokens::find($token)->load('organization');
        } catch (InvalidInvitationException) {
            abort(404);
        }
    }

    private function user(Request $request): Model
    {
        $user = $request->user();

        abort_unless($user instanceof Model, 401);

        return $user;
    }
}
