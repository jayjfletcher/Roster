<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Sso\Actions\UnlinkSsoIdentityAction;
use JayI\Roster\Domains\Sso\Models\SsoIdentityModel;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;

final class UnlinkSsoIdentityMcpRequest extends Request
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

    protected function self(): ?Model
    {
        $actor = $this->actor();

        return $actor !== null && (string) $this->identity()->user_id === (string) $actor->getKey() ? $actor : null;
    }

    private function identity(): SsoIdentityModel
    {
        return $this->resolved ??= SsoIdentityModel::query()->whereKey($this->get('identity'))->firstOrFail();
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
