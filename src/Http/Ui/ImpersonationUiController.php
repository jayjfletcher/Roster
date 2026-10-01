<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Roster\Actions\ListImpersonationsAction;
use JayI\Roster\Actions\StartImpersonationAction;
use JayI\Roster\Actions\StopImpersonationAction;
use JayI\Roster\Http\Ui\Concerns\AuthorizesScreens;
use JayI\Roster\Models\Impersonation;
use JayI\Roster\Support\Scopes;
use JayI\Roster\Support\Users;

/**
 * Atrium: start impersonating from a user's page (straight through the
 * one-time link), and review or end impersonations.
 */
final class ImpersonationUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $filters = $request->validate(ListImpersonationsAction::rules());
        $this->authorizeScreen('roster.users.impersonate', Scopes::fromInput($filters['organization'] ?? null));

        /** @var view-string $view */
        $view = 'roster::ui.impersonations.index';

        return view($view, ['impersonations' => app(ListImpersonationsAction::class)->execute($filters)->withQueryString()]);
    }

    public function start(Request $request, string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.impersonate', Scopes::fromInput($request->input('organization')));
        $actor = $request->user();
        abort_unless($actor instanceof Model, 401);

        $started = app(StartImpersonationAction::class)->execute(
            $this->users->findOrFail($user),
            $request->validate(StartImpersonationAction::rules()),
            $actor,
        );

        return redirect()->to($started->url);
    }

    public function stop(Request $request, string $impersonation): RedirectResponse
    {
        $model = Impersonation::query()->whereKey($impersonation)->firstOrFail();
        $actor = $request->user();
        $own = $actor instanceof Model && (string) $model->impersonator_id === (string) $actor->getKey();
        $this->authorizeScreen('roster.users.impersonate', $model->organization, $own ? $actor : null);

        app(StopImpersonationAction::class)->execute($model, ['why' => $own ? Impersonation::ENDED_STOPPED : Impersonation::ENDED_FORCED]);

        return redirect()
            ->route('atrium.roster.impersonations.index')
            ->with('status', __('roster::roster.impersonation_ended'));
    }
}
