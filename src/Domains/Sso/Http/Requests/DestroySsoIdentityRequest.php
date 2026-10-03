<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Sso\Actions\UnlinkSsoIdentityAction;
use JayI\Roster\Domains\Sso\Models\SsoIdentityModel;
use JayI\Roster\Http\Request;

final class DestroySsoIdentityRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    private ?SsoIdentityModel $resolved = null;

    protected function scope(): ?OrganizationModel
    {
        return $this->identity()->connection?->organization;
    }

    /**
     * Anyone may unlink their own identity.
     */
    protected function self(): ?Model
    {
        $actor = $this->actor();

        return $actor !== null && (string) $this->identity()->user_id === (string) $actor->getKey() ? $actor : null;
    }

    private function identity(): SsoIdentityModel
    {
        return $this->resolved ??= SsoIdentityModel::query()->whereKey($this->route('identity'))->firstOrFail();
    }

    public function rules(): array
    {
        return UnlinkSsoIdentityAction::rules();
    }

    public function persist(): Response
    {
        app(UnlinkSsoIdentityAction::class)->execute($this->identity());

        return response()->noContent();
    }
}
