<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Actions\UnlinkSsoIdentityAction;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\SsoIdentity;
use Laravel\Mcp\Response;

final class UnlinkSsoIdentityMcpRequest extends Request
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

    protected function self(): ?Model
    {
        $actor = $this->actor();

        return $actor !== null && (string) $this->identity()->user_id === (string) $actor->getKey() ? $actor : null;
    }

    private function identity(): SsoIdentity
    {
        return $this->resolved ??= SsoIdentity::query()->whereKey($this->get('identity'))->firstOrFail();
    }

    protected function rules(): array
    {
        return UnlinkSsoIdentityAction::rules() + ['identity' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(UnlinkSsoIdentityAction::class)->execute($this->identity());

        return Response::text('Identity unlinked.');
    }
}
