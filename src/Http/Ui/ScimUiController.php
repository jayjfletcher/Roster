<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Roster\Actions\CreateScimTokenAction;
use JayI\Roster\Actions\RevokeScimTokenAction;
use JayI\Roster\Http\Ui\Concerns\AuthorizesScreens;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\ScimToken;

/**
 * Atrium: issue and revoke an organization's SCIM tokens.
 */
final class ScimUiController
{
    use AuthorizesScreens;

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = Organization::query()->where('slug', $organization)->firstOrFail();
        $this->authorizeScreen('roster.scim.manage', $model);
        $actor = $request->user();

        $issued = app(CreateScimTokenAction::class)->execute(
            $model,
            $request->validate(CreateScimTokenAction::rules()),
            $actor instanceof Model ? $actor : null,
        );

        // Flashed for exactly one page view, then gone.
        return redirect()
            ->route('atrium.roster.organizations.show', [$model, 'tab' => 'scim'])
            ->with('roster_scim_token', $issued->plain)
            ->with('status', __('roster::roster.scim_token_created'));
    }

    public function revoke(string $token): RedirectResponse
    {
        $model = ScimToken::query()->whereKey($token)->firstOrFail();
        $this->authorizeScreen('roster.scim.manage', $model->organization);

        app(RevokeScimTokenAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.show', [$model->organization, 'tab' => 'scim'])
            ->with('status', __('roster::roster.scim_token_revoked'));
    }
}
