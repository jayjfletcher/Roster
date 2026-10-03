<?php

declare(strict_types=1);

namespace JayI\Roster\Atrium\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use JayI\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use JayI\Roster\Domains\Invitation\Actions\RevokeInvitationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

final class InvitationUiController
{
    use AuthorizesScreens;

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.invitations.manage', $model);

        $actor = $request->user();

        app(CreateInvitationAction::class)->execute(
            $model,
            $request->validate(CreateInvitationAction::rules()),
            $actor instanceof Model ? $actor : null,
        );

        return $this->backTo($model, 'roster::roster.invitation_sent');
    }

    public function revoke(string $organization, string $invitation): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.invitations.manage', $model);

        app(RevokeInvitationAction::class)->execute($model->invitations()->whereKey($invitation)->firstOrFail());

        return $this->backTo($model, 'roster::roster.invitation_revoked');
    }

    private function organization(string $slug): OrganizationModel
    {
        return OrganizationModel::query()->where('slug', $slug)->firstOrFail();
    }

    private function backTo(OrganizationModel $organization, string $message): RedirectResponse
    {
        return redirect()
            ->route('atrium.roster.organizations.show', [$organization, 'tab' => 'invitations'])
            ->with('status', __($message));
    }
}
