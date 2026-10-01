<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Roster\Actions\CreateSsoConnectionAction;
use JayI\Roster\Actions\DeleteSsoConnectionAction;
use JayI\Roster\Actions\ShowSsoConnectionAction;
use JayI\Roster\Actions\UnlinkSsoIdentityAction;
use JayI\Roster\Actions\UpdateSsoConnectionAction;
use JayI\Roster\Http\Ui\Concerns\AuthorizesScreens;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\SsoConnection;
use JayI\Roster\Models\SsoIdentity;

/**
 * Atrium: an organization's SSO connections, and unlinking identities.
 */
final class SsoUiController
{
    use AuthorizesScreens;

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = Organization::query()->where('slug', $organization)->firstOrFail();
        $this->authorizeScreen('roster.sso.manage', $model);
        $this->booleans($request);

        $connection = app(CreateSsoConnectionAction::class)->execute($model, $request->validate(CreateSsoConnectionAction::rules()));

        return redirect()
            ->route('atrium.roster.sso.show', $connection->slug)
            ->with('status', __('roster::roster.sso_connection_created'));
    }

    public function show(string $connection): View
    {
        $model = $this->find($connection);
        $this->authorizeScreen('roster.sso.view', $model->organization);

        /** @var view-string $view */
        $view = 'roster::ui.sso.show';

        return view($view, ['connection' => app(ShowSsoConnectionAction::class)->execute($model)]);
    }

    public function update(Request $request, string $connection): RedirectResponse
    {
        $model = $this->find($connection);
        $this->authorizeScreen('roster.sso.manage', $model->organization);
        $this->booleans($request);

        $model = app(UpdateSsoConnectionAction::class)->execute($model, $request->validate(UpdateSsoConnectionAction::rules($model)));

        return redirect()
            ->route('atrium.roster.sso.show', $model->slug)
            ->with('status', __('roster::roster.sso_connection_updated'));
    }

    public function destroy(string $connection): RedirectResponse
    {
        $model = $this->find($connection);
        $this->authorizeScreen('roster.sso.manage', $model->organization);
        $organization = $model->organization;

        app(DeleteSsoConnectionAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.show', [$organization, 'tab' => 'sso'])
            ->with('status', __('roster::roster.sso_connection_deleted'));
    }

    public function unlink(Request $request, string $identity): RedirectResponse
    {
        $model = SsoIdentity::query()->whereKey($identity)->firstOrFail();
        $actor = $request->user();
        $own = $actor instanceof Model && (string) $model->user_id === (string) $actor->getKey();
        $this->authorizeScreen('roster.sso.manage', $model->connection?->organization, $own ? $actor : null);

        $user = $model->user;

        app(UnlinkSsoIdentityAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.users.show', $user instanceof Model ? $user->getRouteKey() : 0)
            ->with('status', __('roster::roster.sso_identity_unlinked'));
    }

    private function find(string $slug): SsoConnection
    {
        return SsoConnection::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Unchecked boxes send nothing; treat them as false.
     */
    private function booleans(Request $request): void
    {
        foreach (['jit', 'enforced', 'enabled'] as $field) {
            $request->merge([$field => $request->boolean($field)]);
        }
    }
}
