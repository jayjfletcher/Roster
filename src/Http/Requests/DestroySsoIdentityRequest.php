<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use JayI\Roster\Actions\UnlinkSsoIdentityAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\SsoIdentity;

final class DestroySsoIdentityRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    private ?SsoIdentity $resolved = null;

    protected function scope(): ?Organization
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

    private function identity(): SsoIdentity
    {
        return $this->resolved ??= SsoIdentity::query()->whereKey($this->route('identity'))->firstOrFail();
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
